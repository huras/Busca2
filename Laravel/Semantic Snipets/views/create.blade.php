//Create holds the form to insert the model, it will call the store route

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <form class="w-100" id='formulario' action={{route('contact.store')}} method='POST'>
        @csrf
        <input type="hidden" name="utm_source" value="{!! (isset($utm_fields['utm_source'])) ? $utm_fields['utm_source'] : '' !!}">
        <input type="hidden" name="utm_medium" value="{!! (isset($utm_fields['utm_medium'])) ? $utm_fields['utm_medium'] : '' !!}">
        <input type="hidden" name="utm_campaign" value="{!! (isset($utm_fields['utm_campaign'])) ? $utm_fields['utm_campaign'] : '' !!}">

        <div class='row'>
            <div class="col-md-6 col-12">
                <label>Nome:</label>
                <input name='nome' value='{{old("nome")}}' placeholder="Nome" class="form-control item crazy_name" type="text" required>
                @if ($errors->has('email'))
                    <span>{{ $errors->first('email') }}</span>
                @endif
            </div>
        </div>
    </form>
</body>
</html>