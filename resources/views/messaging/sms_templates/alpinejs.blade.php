   <script type="text/javascript">
       //initiate data
       function initData() {
           return {
               page_title: "Create SMS template",
               arr_category: {},

               template_form: {
                   action: 'create',
                   id: '',
                   category_id: '',
                   name: '',
                   message: ''
               },
               formLoading: false,
               btnSubmit: "Submit",

               //update data
               updateData(id) {
                   this.page_title = "Update SMS Template";
                   this.template_form.action = "update";
                   this.template_form.id = id;

                   //pull data
                   fetch("/messaging/sms-templates/" + id + "/get-by")
                       .then(response => response.json())
                       .then(data => {
                           this.template_form.name = data.data.name
                           this.template_form.message = data.data.message
                           this.template_form.category_id = data.data.category_id
                       });
               },

               //create or submit form
               createOrUpdateForm() {
                   this.formLoading = true;
                   this.btnSubmit = '<i class="fas fa-spinner"></i> Submitting...'

                   //api for uploading certificate
                   fetch("{{ route('sms-templates.store') }}", {
                           method: "POST",
                           headers: {
                               "Content-Type": "application/json",
                               Accept: "application/json",
                               'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                           },
                           body: JSON.stringify(this.template_form),
                       }).then(response => response.json())
                       .then(responseJson => {
                           $('.message_wrp').html(
                               "<div class='alert alert-success rounded-sm'>" + responseJson.success_msg + "</div>"
                           );

                           // reload the page
                           setTimeout(function() {
                               location.reload();
                           }, 2000);

                       }).catch(() => {
                           //display message
                           $('.message_wrp').html(
                               "<div class='alert alert-danger rounded-sm'>Something went wrong.</div>"
                           );

                       }).finally(() => {
                           this.formLoading = false;
                           this.btnSubmit = 'Submit';
                       });
               },
           }
       }
   </script>
