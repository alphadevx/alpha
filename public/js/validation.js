(function () {
  'use strict'

  // Fetch all the forms we want to apply custom Bootstrap validation styles to
  var forms = document.querySelectorAll('.needs-validation')

  // Loop over them and prevent submission
  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
          Array.from(form.elements).forEach((input) => {
            if (typeof input.pattern  !== 'undefined') {
              
              var rule = input.pattern;

              if (typeof input.dataset.oldrule  !== 'undefined') {
                rule = input.dataset.oldrule;
              }

              if (rule.match(/equal\ to\ /g)) {
                var equalToField = rule.replace(/equal\ to\ /g,'');
                if(input.value === document.getElementById(equalToField).value) {
                
                  input.setCustomValidity("");
                  input.pattern = ".*";
                  input.setAttribute('data-oldrule', rule);
                } else {
                  input.setCustomValidity("invalid");
                }
                input.reportValidity();
              }
            }
          });
        }

        form.classList.add('was-validated')
      }, false)
    })
})()