<?php
/*
 * Plugin Name:       Weight Tracker
 * Description:       Allow users to track their weight over time and add notes.
 */


 add_shortcode('weight_tracker', 'sba_weight_tracker');

 function sba_weight_tracker($atts) {
   ob_start();
   ?>
   
      <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
      <script src="https://code.jquery.com/jquery-3.7.0.js" integrity="sha256-JlqSTELeR4TLqP0OG9dxM7yDPqX1ox/HfgiSLBj8+kM=" crossorigin="anonymous"></script>
      <style>
         .trackingRow{
            display: flex;
            padding: 5px 0;
         }
         div#tracker {
            display: flex;
            flex-direction: column;
            align-items: center;
         }

         .sba_field {
            display: flex;
            flex-direction: column;
            width: 100%;
            min-height: 2em;
            padding: 5px;
         }
         .sba_field input {
            border-radius: 10px;
            border: 1px solid #666;
            min-height: 3em;
            padding: 10px;
         } 
         .sba_field.weight {
            max-width: 6em;
         }
         .sba_field.notes {
            width: 20em;
         }
         .sba_field.date {
            max-width: 9em;
         }

         .buttons{
            display: flex;
            align-self: center;
            justify-content: space-evenly;
            width: 30%;
         }
         #saveBtn, #addBtn {
            padding: 10px 35px!important;
            height: 3em;
            align-self: start;
            margin-top: 20px;
         }
         #saveBtn {
            background-color: var(--e-global-color-primary);
            background-image: none;
         }
         #addBtn {
            background-color: var(--e-global-color-accent);
            background-image: none;
         }
         #message{
            padding: 15px 10px 0;
            color: transparent;
            text-align: center;
         }
         .success{
            color: var(--e-global-color-secondary)!important;
         }
         .error{
            color: red!important;
         }

         :focus-visible, :focus, [type=button]:focus, [type=submit]:focus, button:focus{
            outline-color: var(--e-global-color-accent);
         }

         /* remove arrows from number inputs */
         /* Chrome, Safari, Edge, Opera */
         input::-webkit-outer-spin-button,
         input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
         }
         /* Firefox */
         input[type=number] {
            -moz-appearance: textfield;
         }
         .field-shift {
            left: -9999px;
            position: absolute;
         }

         /** 
         ** Mobile/Tablet
         **/
         @media all and (max-width: 554px){
            .trackingRow {
               flex-wrap: wrap;
            }   
            SBARow:nth-child(odd){
               background-color: var(--e-global-color-c960770);
               border-bottom: 1px solid var(--e-global-color-secondary);
               border-top: 1px solid var(--e-global-color-secondary);
            }
            .sba_field.weight, .sba_field.date {
               max-width: 50%;
            }
            .sba_field.notes {
               min-width: 100%;
            }
         }

      </style>

      <script type="module">
         const { createApp } = Vue;
         createApp({
            data() {
               return {
                  rows: 0,
                  weights: [],
                  data: (sba_main_js_vars.weights ? JSON.parse(sba_main_js_vars.weights.toString()) : []),
                  message: ''
               }
            },
            created(){
               this.rows = this.data.length;
            },
            methods:{
               addRow(){
                  this.data.push(
                     {
                        weight: '',
                        date: '',
                        notes: '',
                     }
                  );
                  console.log(this.data);
                  this.rows+=1;
               },
               saveRow(n){
                  sba_main_js_vars = this.data; //assigning to sba_main_js_vars just seems to send the data better
                  //console.log(sba_main_js_vars);
                  jQuery(document).ready(function($){ //send data from JavaScript to PHP using AJAX
                    $.ajax({
                        url: '/wp-admin/admin-ajax.php',
                        data: {
                           'action': 'saveRow',
                           'weightData': sba_main_js_vars
                        },
                        success: function(data){
                           console.log("saveRow ajax in javascript worked");
                           $('#message').addClass("success");
                           $('#message').html("Saved!");                  
                        },
                        error: function(xhr, status, error){
                           console.log("saveRow ajax javascript didn't work:"+error);
                           $('#message').addClass("error");
                           $('#message').html("There was an error saving. Please try again or refresh the page and re-enter your data. If this continues to happen, please contact us.");
                        }
                     });
                  });
               },
            }
         }).mount('#tracker');

      </script>

      <div id="tracker">


         <SBARow v-for="(n, index) in rows" key="n">
            <span :id="'row-'+n" class="trackingRow">
               <span class="sba_field date">
                  <label>Date</label>
                  <input :id="'date-'+n" type="date" v-model="this.data[index].date">
               </span>
               <span class="sba_field weight">
                  <label>Weight (lb)</label>
                  <input :id="'weight-'+n" type="number" v-model="this.data[index].weight">
               </span>
               <span class="sba_field notes">
                  <label>Notes</label>
                  <input :id="'notes-'+n" type="textarea" v-model="this.data[index].notes">
               </span>
            </span>
         </SBARow>
         <div class="buttons">
            <input type="button" id="addBtn" @click="this.addRow()" value="+Add">
            <input type="button" id="saveBtn" @click="this.saveRow(n)" value="SAVE">
         </div>
         <span id="message">{{ message }}</span>
      </div>

   <?php
   return ob_get_clean();
}



/**
   * Pass user data from PHP to JS
   * 
   * @return void
*/

function sba_enqueue_scripts() {
   wp_register_script( 'sba-main-js', get_stylesheet_directory_uri() . '/main.js', [], null, true ); 
   
   
   //grab user profile fields here
   $user = wp_get_current_user(); //get the logged in user's ID [WORKING]
   $user_info = get_user_meta($user->ID, 'weights', true); //get weights field for logged in user [WORKING]
   
   $sba_main_js_vars = [
    'weights' => __($user_info)
   ];
   //echo var_dump($sba_main_js_vars); //TESTING above code [WORKS]

  
   wp_localize_script('sba-main-js', 'sba_main_js_vars', $sba_main_js_vars);

   wp_enqueue_script('sba-main-js');
}
  
  add_action( 'wp_enqueue_scripts', 'sba_enqueue_scripts', 100 );


  //get data from AJAX in trackingWidget.html and echo it in the response. Next step will be to save it to the user's profile in db
  function save_row(){
   if(isset($_REQUEST)){
      $user = wp_get_current_user();
      $weights = $_REQUEST['weightData'];

      var_dump($weights[0]["weight"]);//this is how to get a single value. PHP recognizes the object as a multidimensional array
      //dumps in Network response of inspector

      var_dump(json_encode($weights));

      //NOW STORE THE WEIGHTS VARIABLE/ARRAY TO THE USER'S PROFILE IN DB
      $saveToDb = update_user_meta( $user->ID, 'weights', json_encode($weights, JSON_PRETTY_PRINT), '');

      die();
   }
  }
  add_action('wp_ajax_saveRow', 'save_row', 101);


function custom_user_profile_contact_fields( $methods ) {
   $methods['weights'] = 'Weights';


   return $methods;
}
add_action( 'user_contactmethods', 'custom_user_profile_contact_fields' );

