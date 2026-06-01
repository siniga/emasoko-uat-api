   <script type="text/javascript">
       //initiate data
       function initData() {
           return {
               templates: [],
               smsForm: {
                   template_id: '',
                   message: '',
               },

               fetchTemplates() {
                   fetch('/messaging/sms-templates/all')
                       .then(res => res.json())
                       .then(data => {
                           this.templates = data.data || [];
                           // Re-init select2 after options render (safe if already initialized)
                           this.$nextTick(() => {
                               if ($(this.$refs.template_id).hasClass('select2-hidden-accessible')) {
                                   $(this.$refs.template_id).trigger(
                                       'change.select2'); // keep UI/state in sync
                               }
                           });
                       });
               },

               onTemplateChange(selectedValue) {
                   console.log("== onTemplateChange ==")
                   this.smsForm.message = "";

                   fetch("/messaging/sms-templates/" + selectedValue + "/get-by")
                       .then(response => response.json())
                       .then(data => {
                           console.log(data.data)
                           this.smsForm.message = data.data.message
                       });
               },
           }
       }
   </script>
