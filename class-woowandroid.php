<?php 
/**
 * Class Notification Email
 * 
 * Feature :
 * Logging
 * On Order
 * On Complete
 * Settings
 * 
 */
Class LSDDonationWooWandroid Extends LSDD_Notification {
    public $id       = 'lsdd_notification_woowandroid';
    public $name     = 'WooWandroid';
    public $type     = 'whatsapp';
    public $doc_url  = 'https://docs.lsdplugins.com/docs/cara-menggunakan-notifikasi-whatsapp/';

    protected $settings         = array();
    protected $log              = array();
    protected $order_message    = null;
    protected $complete_message = null;
    public $server              = null;
    public $apikey              = null;

    public function __construct() {

        // TestCase : Data Empty
        // update_option( $this->id,null );
$template_order = 'Kepada YTH Bpk/Ibu *%donors%*
Berikut ini Pesanan Anda :
*%program%*

Total Pembayaran :
%total%

Silahkan Selesaikan Pembayaran
%payment%

%code_label% %code_value%
%account_label% %account_code% %account_number%
%holder_label% %holder_value%

%instruction_text%

Salam Hangat
*LSDDonasi*';

$template_complete = 'Terimakasih *%donors%*
atas donasi yang telah Anda berikan

Donasi %program% akan kami sampaikan 
kepada orang-orang yang membutuhkan

Semoga menjadi amal ibadah anda 
dan Tuhan memberi keberkahan 
atas apa yang Anda berikan.

Salam Hangat
*LSDDonasi*';

        if( empty( get_option( $this->id ) ) ){
            $new = array();
            $new['messages']['order']       = $template_order;
            $new['messages']['complete']    = $template_complete;
            $new['settings']['apikey']      = '';
            update_option( $this->id , $new );
        }

    
        $settings               = get_option( $this->id ); 
        $this->order_message    = $settings['messages']['order'];
        $this->complete_message = $settings['messages']['complete'];
        $this->apikey           = $settings['settings']['apikey'];

        if( empty( get_option( $this->id )['messages']['order'] ) ||  empty( get_option( $this->id )['messages']['complete'] ) ){
            $new['messages']['order']       = $template_order;
            $new['messages']['complete']    = $template_complete;
            $new['settings']['apikey']      = $settings['settings']['apikey'];
            update_option( $this->id , $new );
        }

        add_action( 'wp_ajax_nopriv_lsdd_notification_woowandroid_test', array( $this, 'woowandroid_test' ) );
        add_action( 'wp_ajax_lsdd_notification_woowandroid_test', array( $this, 'woowandroid_test' ) );

        add_action( 'wp_ajax_nopriv_lsdd_notification_woowandroid_save', array( $this, 'woowandroid_save' ) );
        add_action( 'wp_ajax_lsdd_notification_woowandroid_save', array( $this, 'woowandroid_save' ) );
        
        add_action( 'lsdd_notification_hook', array( $this, 'lsdd_register_woowandroid_notification') );
    }

    public function lsdd_register_woowandroid_notification( $data ){
        if( lsdd_get_notification_status(  $this->id ) ) {
            $settings = get_option(  $this->id );
            $event = isset($data['notification_event']) ?  $data['notification_event'] : '';
            $phone = isset($data['phone']) ?  $data['phone'] : '';
        
            $template = $settings['messages'][$event]; //get message by event
        
            if( $template ){
                $this->send_whatsapp( $phone, $event, $template, $data );
            }else{
                $this->log_woowandroid( $phone, 'on ' . $event, __('please set template first', 'lsdd') );
            }
        }
    }

    public function log_woowandroid( $reciever, $event , $message ){
        $db = get_option( $this->id ); /// Get Log
        $log = isset( $db['log'] ) ? $db['log'] : array(); // Check Log
        if( count($log) >= 30 ) $log = array(); // Auto Reset Log

        $log[] = array( lsdd_date_now(), $reciever, $event, $message); // Push New Log
        $db['log'] = $log; // Set Log
    
        update_option( $this->id, $db ); // Saving Log
    }

    public function send_whatsapp( $reciever, $event, $message, $data ){

        if( lsdd_get_notification_status( $this->id ) || $data == 'test' ){

            $settings = get_option( $this->id );
            

            if( $data != 'test' ){
                $payment = esc_attr( $data['gateway'] );
                $message = str_replace("%donors%", esc_attr( ucfirst( $data['name'] ) ), $message);
                $message = str_replace("%program%", get_the_title( $data['program_id'] ), $message);
                $message = str_replace("%total%", lsdd_currency_format( true, lsdd_currency_clear( $data['total'] ) ), $message);
                $message = str_replace("%payment%", esc_attr( $data['payment_name'] ) , $message);

                $message = str_replace("%code_label%", esc_attr( $data['code_label'] ) , $message);
                $message = str_replace("%code_value%", esc_attr( $data['code_value'] ), $message);

                $message = str_replace("%account_label%", esc_attr( $data['account_label'] ) , $message);
                $message = str_replace("%account_code%", esc_attr( $data['account_code'] ), $message);
                $message = str_replace("%account_number%", esc_attr( $data['account_number'] ) , $message);

                $message = str_replace("%holder_label%", esc_attr( $data['holder_label'] ), $message);
                $message = str_replace("%holder_value%", esc_attr( $data['holder_value'] ) , $message);

                $message = str_replace("%instruction_text%", esc_attr( $data['instruction_text'] ) , $message);
            }

            switch ($event) {
                case 'order':
                    $tipe = 'Donasi Diterima';
                    break;
                case 'complete':
                    $tipe = 'Donasi Berhasil';
                    break;

            }
            $csID   = isset( $settings['settings']['apikey'] ) ? esc_attr( $settings['settings']['apikey'] ) : '';
            $param = array(
                'app_id' => '429d3472-da0f-4b2b-a63e-4644050caf8f',
                'include_player_ids' => [$csID],
                'data' => array(
                    "type" => $tipe,
                    "message" => $message,
                    "no_wa" => $reciever
                ),
                'contents' => array(
                    "en" => $tipe . " !!!"
                ),
                "headings" => array(
                    "en" => "LSD Donasi"
                )
            );
        
            $pload = array(
                'method' => 'POST',
                'timeout' => 30,
                'headers'     => array(
                    'Authorization' => 'NjY0NzE3MTYtMzc3ZC00YmY5LWJhNzQtOGRiMWM1ZTNhNzBh',
                    'Content-Type'  => 'application/json',
                ),
                'httpversion' => '1.0',
                'sslverify' => false,
                'body' => json_encode($param),
                'cookies' => array()
            );
            
            $response = wp_remote_post( "https://onesignal.com/api/v1/notifications", $pload);
            // $response_back = json_decode(wp_remote_retrieve_body( $response ), TRUE );
            $this->log_woowandroid( $reciever, $event, $response['response']['message'] );
            return $response['response']['code'];
        }
    }

    public function woowandroid_test(){
        if ( ! check_ajax_referer( 'lsdd_nonce', 'security' ) )  wp_send_json_error( 'Invalid security token sent.' );

        $_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );
        $phone      = esc_attr( $_REQUEST['phone'] );

        $this->send_whatsapp( $phone, 'on test', 'LSDDonation : Whatsapp Notification Test using WooWandroid', 'test' );
        echo 'action_success';
        wp_die();
    }

    public function woowandroid_save(){
        if ( ! check_ajax_referer( 'lsdd_nonce', 'security' ) )  wp_send_json_error( 'Invalid security token sent.' );

        $_REQUEST  = array_map( 'stripslashes_deep', $_REQUEST );
        $type      = $_REQUEST['type'] == 'order' ? 'order' : 'complete';
        $content   = $_REQUEST['content'];

        // Saving Template
        $option = get_option( $this->id );
        $option['messages'][$type] = $content;
        update_option( $this->id , $option );

        echo 'action_success';
        wp_die();
    }

    public function manage(){ 
        $this->name = strtolower( $this->name );
        ?>
        <div class="tabs-wrapper">
            <input type="radio" name="<?php echo $this->name; ?>" id="log_<?php echo $this->name; ?>" checked="checked"/>
            <label class="tab" for="log_<?php echo $this->name; ?>"><?php _e( 'Log', 'lsdd' ); ?></label>

            <input type="radio" name="<?php echo $this->name; ?>" id="order_<?php echo $this->name; ?>"/>
            <label class="tab" for="order_<?php echo $this->name; ?>"><?php _e( 'On Order', 'lsdd' ); ?></label>

            <input type="radio" name="<?php echo $this->name; ?>" id="complete_<?php echo $this->name; ?>"/>
            <label class="tab" for="complete_<?php echo $this->name; ?>"><?php _e( 'On Complete', 'lsdd' ); ?></label>

            <input type="radio" name="<?php echo $this->name; ?>" id="settings_<?php echo $this->name; ?>"/>
            <label class="tab" for="settings_<?php echo $this->name; ?>"><?php _e( 'Settings', 'lsdd' ); ?></label>

            <div class="tab-body-wrapper">
                 <!------------ Tab : Log ------------>
                <div id="tab-body-<?php echo $this->name; ?>-log" class="tab-body">
                    <table class="table-log table table-striped table-hover">
                        <tbody>
                        <?php 
                            $db = get_option( $this->id );
                            $log = isset( $db['log'] ) ? $db['log'] : array();
                        ?>
                        <?php if( $log ) : ?>
                        <?php foreach ( array_reverse( $log ) as $key => $value) : ?>
                            <tr>
                                <td><?php echo lsdd_date_format( $value[0], 'j M Y, H:i:s' ); ?></td>
                                <td><?php echo $value[1]; ?></td>
                                <td><?php echo $value[2]; ?></td>
                                <td><?php echo $value[3]; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php else:  ?>
                            <tr><td><?php _e( 'Empty Log', 'lsdd-qurban' ); ?></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!------------ Tab : On Order ------------>
                <div id="tab-body-<?php echo $this->name; ?>-order" class="tab-body">

                    <div class="columns col-gapless">
                        <!-- Email Instruction and Editor -->
                        <div class="column col-3" style="padding:0 10px 0 0;">
        
                            <div id="instruction">
                                <!-- Marker -->
                                <p id="tag" class="mt-2">
                                    <kbd>%donors%</kbd><code>John Doe</code><br>
                                    <kbd>%program%</kbd><code>Bantu Sesama</code> <br>
                                    <kbd>%total%</kbd><code>Rp 10.000</code> <br>
                                    <kbd>%payment%</kbd><code>Transfer - BCA</code> <br>
                                    <kbd>%code_label%</kbd><code>BIC/SWIFT : </code> <br>
                                    <kbd>%code_value%</kbd><code>BRINIDJA</code> <br>
                                    <kbd>%account_label%</kbd><code>Rekening : </code> <br>
                                    <kbd>%account_code%</kbd><code>(014)</code> <br>
                                    <kbd>%account_number%</kbd><code>6541217162</code> <br>
                                    <kbd>%holder_label%</kbd><code>Atas Nama : </code> <br>
                                    <kbd>%holder_value%</kbd><code>lsdplugins@gmail.com</code> <br>
                                    <kbd>%instruction_text%</kbd><code>Instruksi berdasarkan metode pembayaran</code> <br>
                                </p>
                            </div>

                            <button data-type="order" class="btn btn-primary input-group-btn lsdd_woowandroid_templates_save"><?php _e( 'Save', 'lsdd' ); ?></button> 
                        </div>

                        <!-- Email Preview -->
                        <div class="column col-8" style="margin-left:35px;">
                            <!-- Migration Alert -->
                            <textarea data-event="order" class="form-input lsdd_woowandroid_message_templates" placeholder="Pesan Notifikasi Untuk Donatur ketika Memesan" rows="14"><?php echo esc_attr( $this->order_message ); ?></textarea>
                        </div>

                    </div>

                </div>
                <!------------ Tab : On Complete ------------>
                <div id="tab-body-<?php echo $this->name; ?>-complete" class="tab-body">

                    <div class="columns col-gapless">
                        <!-- Email Instruction and Editor -->
                        <div class="column col-3" style="padding:0 10px 0 0;">

                            <div id="instruction">
                                <!-- Subject Email -->
                                <p id="tag" class="mt-2">
                                    <kbd>%donors%</kbd><code>John Doe</code><br>
                                    <kbd>%program%</kbd><code>Bantu Sesama</code><br>
                                </p>
                            </div>

                            <button data-type="complete" class="btn btn-primary input-group-btn lsdd_woowandroid_templates_save"><?php _e( 'Save', 'lsdd' ); ?></button> 
                        </div>

                        <div class="column col-8" style="margin-left:35px;">
                            <!-- Migration Alert -->
                            <textarea data-event="complete" class="form-input lsdd_woowandroid_message_templates" placeholder="Pesan Notifikasi Untuk Donatur Ketika Pembayaran Berhasil" rows="14"><?php echo esc_attr( $this->complete_message ); ?></textarea>
                        </div>
                    </div>
                    <!-- Content Email ketika Lunas -->
                </div>
                <!------------ Tab : Settings ------------>
                <div id="tab-body-<?php echo $this->name; ?>-settings" class="tab-body">
                    <!-- Content Pengaturan -->
                    <form class="form-horizontal" block="settings">

                        <!-- Sender -->
                        <!-- <div class="form-group">
                            <div class="col-3 col-sm-12">
                            <label class="form-label" for="country"><?php //_e( 'Domain API', "lsdd-woowandroid" ); ?></label>
                            </div>
                            <div class="col-9 col-sm-12">
                            <input class="form-input" type="text" name="server" placeholder="console.woowandroid.com" style="width:320px" value="<?php //esc_attr_e( isset( $this->server ) ? $this->server : null ); ?>">
                            </div>
                        </div> -->

                        <!-- Sender Email -->
                        <div class="form-group">
                            <div class="col-3 col-sm-12">
                            <label class="form-label" for="country"><?php _e( 'CS ID', "lsdd-woowandroid" ); ?></label>
                            </div>
                            <div class="col-9 col-sm-12">
                            <input class="form-input" type="password" autocomplete="off"  name="apikey" placeholder="B8as91na12m1nn1243nS1-2131-4124-n24An1n021" style="width:320px" value="<?php esc_attr_e( isset( $this->apikey ) ? $this->apikey : null ); ?>">
                            </div>
                        </div>

                        <button class="btn btn-primary lsdd_admin_option_save" option="<?php echo $this->id; ?>" style="width:120px"><?php _e( 'Save', "lsdd-woowandroid" ); ?></button> 
                    </form>
      
                    <div class="divider" data-content="Test woowandroid Notification"></div>
                    <div class="input-group" style="width:50%;">
                        <input id="lsdd_woowandroid_test" style="margin-top:3px;" class="form-input input-md" type="text" placeholder="0812387621812">
                        <button id="lsdd_woowandroid_sendtest" style="margin-top:3px;" class="btn btn-primary input-group-btn"><?php _e( 'Test Notification', "lsdd-woowandroid" ); ?></button>
                    </div>
                </div>
            </div>

        </div>

        <style>
            #lsdd-editor{
                height:100%;
                margin-top: 20px;
            }

            #lsdd-editor-order img,
            #lsdd-editor-complete img{
                cursor: pointer;
            }

            /* Action Tab */
            #log_woowandroid:checked~.tab-body-wrapper #tab-body-woowandroid-log,
            #order_woowandroid:checked~.tab-body-wrapper #tab-body-woowandroid-order,
            #complete_woowandroid:checked~.tab-body-wrapper #tab-body-woowandroid-complete,
            #settings_woowandroid:checked~.tab-body-wrapper #tab-body-woowandroid-settings {
                position: relative;
                top: 0;
                opacity: 1
            }
            .tab-body-wrapper .table-log th{
                display: inline-block;
            }
            .tab-body-wrapper .table-log tr{
                margin-bottom: 0; 
            }

            .tab-body-wrapper .table-log tbody tr td{
                display: inline-block !important;
                padding: 10px !important;
            }

            .tab-body-wrapper .table-log.table td, .tab-body-wrapper .table-log.table th{
                border-bottom: 0;
            }
            .wp-picker-container{
                display:block;
            }
        </style>

        <script>
            // woowandroid Save Template
            jQuery(document).on("click",".lsdd_woowandroid_templates_save",function( e ) {
                jQuery(this).addClass('loading');
                let type    = jQuery(this).attr('data-type');
                let content = jQuery('.lsdd_woowandroid_message_templates[data-event="'+ type +'"]').val();
                let that    = this;

                jQuery.post( lsdd_adm.ajax_url, { 
                    action  : 'lsdd_notification_woowandroid_save',
                    type    : type,
                    content : content,
                    security : lsdd_adm.ajax_nonce,
                    }, function( response ){
                        if( response.trim() == 'action_success' ){
                            jQuery(that).removeClass('loading');
                        }
                    }).fail(function(){
                        alert('Failed, please check your internet');
                        location.reload();
                    }
                );
            });

            // On User Sending Test Email
            jQuery(document).on("click","#lsdd_woowandroid_sendtest",function( e ) {
                var woowandroid_number = jQuery('#lsdd_woowandroid_test').val();

                if( woowandroid_number != '' ){
                    jQuery(this).addClass('loading');
                    jQuery('#lsdd_woowandroid_test').css('border', 'none');
                    
                    jQuery.post( lsdd_adm.ajax_url, { 
                        action : 'lsdd_notification_woowandroid_test',
                        phone  : woowandroid_number,
                        security : lsdd_adm.ajax_nonce,
                        }, function( response ){
                            if( response.trim() == 'action_success' ){
                                location.reload();
                            }
                        }).fail(function(){
                            alert('Failed, please check your internet');
                        }
                    );

                }else{
                    jQuery('#lsdd_woowandroid_test').css('border', '1px solid red');
                }
            });

        </script>
    <?php
    }
}
lsdd_notification_register( 'LSDDonationWooWandroid' );
?>
