<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=<H, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    @php
        $parent = $folder->parent;
        $breadcrumb = [];
    @endphp
    @while(isset($parent))
        @php
            $breadcrumb[] = [
                'name' => $parent->name,
                'id' => $parent->id
            ];

            $parent = $parent->parent;
        @endphp
    @endwhile
    <div class='dflex'>
        /
        @foreach(array_reverse($breadcrumb) as $bread)
            <a href='/folders/{{$bread["id"]}}'> {{$bread["name"]}} </a> /
        @endforeach
        <span> {{$folder->name}} </span>
    </div>

    <br>
    <form action="/folders/store" method="POST">
        @csrf
        <input name='name' type='text'>
        <input name='parent_id' type='hidden' value='{{$folder->id}}'>
        <input type='submit' value='+ Folder'>
    </form>
    <br>
    <form action="/files/store" method="POST" enctype="multipart/form-data">
        @csrf
        <input name='file' type='file'>
        <input name='folder_id' type='hidden' value='{{$folder->id}}'>
        <input type='submit' value='Upload File'>
    </form>
    <br>

    @if(count($folder->childrenFolders) > 0)
        <h2>Children Folders</h2>
        @foreach($folder->childrenFolders as $item)
            <div>
                <span> {{$item->id}} </span>
                |<span> {{$item->name}} </span>
                |<span> {{ (count($item->childrenFolders) > 0) ? count($item->childrenFolders).' folders inside' : 'No folders inside' }} </span>
                @if(isset($item->parent))
                    |<span> Parent: {{$item->parent->name}} </span>
                @endif
                | <a href='/folders/{{$item->id}}' > <button>view</button>  </a>
            </div>
        @endforeach
    @else
        <h2> Não há pastas </h2>
    @endif

    @if(count($folder->childrenFiles) > 0)
        <h2>Children Files</h2>
        @foreach($folder->childrenFiles as $item)
            <div style='display: flex;'>
                <span> {{$item->id}} </span>
                |<span> {{$item->name}} </span>
                |<span> {{$item->size}} </span>
                |<a href='/files/download/{{$item->id}}' > <input type='submit' value='Download'> </a>
                |<form action="/files/delete/{{$item->id}}" method='POST'>
                    @method('DELETE')
                    @csrf
                    <input type="submit" value='Deletar'>
                </form>
            </div>
        @endforeach
    @else
        <h2> Não há arquivos </h2>
    @endif

</body>
</html>
