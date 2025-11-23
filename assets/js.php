<script>
  $(document).ready(function() {

    // Seleccionamos los inputs por su atributo 'name'
    var nameInputs = $('input[name="first_name"], input[name="last_name"], input[name="phone"]');

    // 1. EVENTO INPUT: Validación mientras escriben
    nameInputs.on('input', function() {
        var node = $(this);
        
        // Expresión regular: 
        // [^a-zA-ZáéíóúÁÉÍÓÚñÑ\s] -> Busca todo lo que NO sea letras, tildes, ñ o espacios.
        node.val(node.val().replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, ''));
    });

    // 2. EVENTO BLUR: Limpieza cuando terminan de escribir
    nameInputs.on('blur', function() {
        var node = $(this);
        
        // .trim() elimina los espacios vacíos al inicio y al FINAL del texto
        var currentVal = node.val();
        var trimmedVal = currentVal.trim();
        
        node.val(trimmedVal);

        // Opcional: Validación visual si quedó vacío después de limpiar
        if(trimmedVal === "") {
            node.css('border', '1px solid red');
        } else {
            node.css('border', ''); // Regresa al borde original
        }
    });

});

$(document).ready(function() {
    // 1. Selecciona el formulario usando el atributo data-validate o el tag 'form'
    $('form[data-validate]').submit(function(e) {
        
        // 2. Obtiene el valor del campo de la contraseña usando su atributo 'name'
        var password = $('input[name="password"]').val();
        
        // 3. Define los límites de la longitud
        var minLength = 8;
        var maxLength = 16;
        
        // 4. Realiza la validación
        if (password.length < minLength || password.length > maxLength) {
            // Si la validación falla:
            
            // a) Previene el envío del formulario
            e.preventDefault();
            
            // b) Muestra un mensaje de error (puedes adaptarlo al estilo de tu aplicación)
            alert('La contraseña debe tener entre ' + minLength + ' y ' + maxLength + ' caracteres.');
            
            // c) Enfoca el campo de la contraseña para que el usuario pueda corregirlo
            $('input[name="password"]').focus();
            
            // d) Retorna false para detener el proceso
            return false;
        }
        
        // Si la validación es exitosa, el formulario se enviará
        // return true; // (Implícito al no llamar a e.preventDefault())
    });
});

/*********************************************  Validacion para uso de correos @gmail.com  *****************************************************************/
    $(document).ready(function () {
      // Crear elemento para mensaje de error
      $('input[name="email"]').after('<div class="error-message" style="display:none; color:#ff3860; font-size:12px; margin-top:4px;">Solo se permiten correos @gmail.com</div>');

      $('form[data-validate]').on('submit', function (e) {
        var email = $('input[name="email"]').val().trim();
        var errorMessage = $('.error-message');

        if (!email.endsWith('@gmail.com')) {
          e.preventDefault();

          // Mostrar mensaje de error
          errorMessage.show();

          // Enfocar el campo email
          $('input[name="email"]').focus();
          $('input[name="email"]').addClass('error');

          return false;
        }

        // Si es válido, ocultar mensaje y quitar estilo de error
        errorMessage.hide();
        $('input[name="email"]').removeClass('error');
      });

      // Validación en tiempo real
      $('input[name="email"]').on('input', function () {
        var email = $(this).val().trim();
        var errorMessage = $('.error-message');

        if (email !== '' && !email.endsWith('@gmail.com')) {
          $(this).addClass('error');
          errorMessage.show();
        } else {
          $(this).removeClass('error');
          errorMessage.hide();
        }
      });
    });

</script>
