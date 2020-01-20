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
                <input  name='email' 
                        value='{{old("nome")}}' 
                        placeholder="Nome" 
                        class="form-control item crazy_name @error('email') has-error @enderror" 
                        type="text" 
                        required
                >
                @error('email')
                    <span>{{ $message }}</span>
                @enderror
            </div>
            
            <div class='f-group'>
                <div id="recaptcha-div"></div>
                <br/>
                @if(session('error-recaptcha'))
                    <div class='mensagem-erro'>
                        Por favor, faça o teste anti robo.
                    </div>
                @endif
            </div>
            
            <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>
            <script>
                var onloadCallback = function() {
                grecaptcha.render('recaptcha-div', {
                  'sitekey' : 'asd5qwe3AAC-p-qsd1qw23es8df723r-Ksad9834j'
                });
              };
            </script>
        </div>
    </form>
</body>
</html>
