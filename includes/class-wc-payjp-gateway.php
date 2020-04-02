<?php

if (!defined('ABSPATH')) exit;

if (!class_exists('WC_Payjp_Gateway')) {

    class WC_Payjp_Gateway extends WC_Payment_Gateway {

        const ERROR_MESSAGES = [
            'invalid_number' => '不正なカード番号',
            'invalid_cvc' => '不正なCVC',
            'invalid_expiry_month' => '不正な有効期限月',
            'invalid_expiry_year' => '不正な有効期限年',
            'expired_card' => '有効期限切れ',
            'card_declined' => 'カード会社によって拒否されたカード',
            'processing_error' => '決済ネットワーク上で生じたエラー',
            'missing_card' => '顧客がカードを保持していない',
            'invalid_id' => '不正なID',
            'no_api_key' => 'APIキーがセットされていない',
            'invalid_api_key' => '不正なAPIキー',
            'invalid_plan' => '不正なプラン',
            'invalid_expiry_days' => '不正な失効日数',
            'unnecessary_expiry_days' => '失効日数が不要なパラメーターである場合',
            'invalid_flexible_id' => '不正なID指定',
            'invalid_timestamp' => '不正なUnixタイムスタンプ',
            'invalid_trial_end' => '不正なトライアル終了日',
            'invalid_string_length' => '不正な文字列長',
            'invalid_country' => '不正な国名コード',
            'invalid_currency' => '不正な通貨コード',
            'invalid_address_zip' => '不正な郵便番号',
            'invalid_amount' => '不正な支払い金額',
            'invalid_plan_amount' => '不正なプラン金額',
            'invalid_card' => '不正なカード',
            'invalid_customer' => '不正な顧客',
            'invalid_boolean' => '不正な論理値',
            'invalid_email' => '不正なメールアドレス',
            'no_allowed_param' => 'パラメーターが許可されていない場合',
            'no_param' => 'パラメーターが何もセットされていない',
            'invalid_querystring' => '不正なクエリー文字列',
            'missing_param' => '必要なパラメーターがセットされていない',
            'invalid_param_key' => '指定できない不正なパラメーターがある',
            'no_payment_method' => '支払い手段がセットされていない',
            'payment_method_duplicate' => '支払い手段が重複してセットされている',
            'payment_method_duplicate_including_customer' => '支払い手段が重複してセットされている(顧客IDを含む)',
            'failed_payment' => '指定した支払いが失敗している場合',
            'invalid_refund_amount' => '不正な返金額',
            'already_refunded' => 'すでに返金済み',
            'cannot_refund_by_amount' => '返金済みの支払いに対して部分返金ができない',
            'invalid_amount_to_not_captured' => '確定されていない支払いに対して部分返金ができない',
            'refund_amount_gt_net' => '返金額が元の支払い額より大きい',
            'capture_amount_gt_net' => '支払い確定額が元の支払い額より大きい',
            'invalid_refund_reason' => '不正な返金理由',
            'already_captured' => 'すでに支払いが確定済み',
            'cant_capture_refunded_charge' => '返金済みの支払いに対して支払い確定ができない',
            'charge_expired' => '認証が失効している支払い',
            'alerady_exist_id' => 'すでに存在しているID',
            'token_already_used' => 'すでに使用済みのトークン',
            'already_have_card' => '指定した顧客がすでに保持しているカード',
            'dont_has_this_card' => '顧客が指定したカードを保持していない',
            'doesnt_have_card' => '顧客がカードを何も保持していない',
            'invalid_interval' => '不正な課金周期',
            'invalid_trial_days' => '不正なトライアル日数',
            'invalid_billing_day' => '不正な支払い実行日',
            'exist_subscribers' => '購入者が存在するプランは削除できない',
            'already_subscribed' => 'すでに定期課金済みの顧客',
            'already_canceled' => 'すでにキャンセル済みの定期課金',
            'already_pasued' => 'すでに停止済みの定期課金',
            'subscription_worked' => 'すでに稼働している定期課金',
            'test_card_on_livemode' => '本番モードのリクエストにテストカードが使用されている',
            'not_activated_account' => '本番モードが許可されていないアカウント',
            'too_many_test_request' => 'テストモードのリクエストリミットを超過している',
            'invalid_access' => '不正なアクセス',
            'payjp_wrong' => 'PAY.JPのサーバー側でエラーが発生している',
            'pg_wrong' => '決済代行会社のサーバー側でエラーが発生している',
            'not_found' => 'リクエスト先が存在しないことを示す',
            'not_allowed_method' => '許可されていないHTTPメソッド',
        ];

        public static $log_enabled = false;
        public static $log = null;

        public function __construct() {
            $this->id = "payjp";
            $this->icon = "https://d3vq62w6khyz8s.cloudfront.net/assets-6289ce022d6cfa5e6b737e144753a56cb5f9d4df/merchant/images/merchant/favicon.png";
            $this->method_title = "PAY.JP Gateway";
            $this->method_description = __("様々なサービスにクレジットカード決済を無料で簡単に導入できる開発者向けのオンライン決済サービスです。", "woocommerce-payjp");

            $this->supports = array(
                'products'
            );
            $this->init_form_fields();
            $this->init_settings();
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            $this->testmode = 'yes' === $this->get_option('testmode');
            $this->private_key = $this->testmode ? $this->get_option('test_private_key') : $this->get_option('private_key');
            $this->publishable_key = $this->testmode ? $this->get_option('test_publishable_key') : $this->get_option('publishable_key');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

        public function get_error_message($code) {
           $error_messages = self::ERROR_MESSAGES;
           if (isset($error_messages[$code])) {
              return __($error_messages[$code], 'woocommerce-payjp');
           } else {
              return __("別の決済方法をご利用いただくか、管理者にお問い合わせください。", 'woocommerce-payjp');
           }
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('有効/無効', 'woocommerce-payjp'),
                    'label' => __('有効 PAY.JP', 'woocommerce-payjp'),
                    'type' => 'checkbox',
                    'description' => '',
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => __('タイトル', 'woocommerce-payjp'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-payjp'),
                    'default' => __('PAY.JP', 'woocommerce-payjp'),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('説明', 'woocommerce-payjp'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-payjp'),
                    'default' => __('Pay with your credit card PAY.JP payment gateway', 'woocommerce-payjp'),
                ),
                'testmode' => array(
                    'title' => __('テストモード', 'woocommerce-payjp'),
                    'label' => __('有効テストモード', 'woocommerce-payjp'),
                    'type' => 'checkbox',
                    'description' => __('Place the payment gateway in test mode using test API keys.', 'woocommerce-payjp'),
                    'default' => 'yes',
                    'desc_tip' => true,
                ),
                'test_private_key' => array(
                    'title' => __('テスト秘密鍵', 'woocommerce-payjp'),
                    'type' => 'password',
                ),
                'test_publishable_key' => array(
                    'title' => __('テスト公開鍵', 'woocommerce-payjp'),
                    'type' => 'text'
                ),
                'private_key' => array(
                    'title' => __('本番秘密鍵', 'woocommerce-payjp'),
                    'type' => 'password'
                ),
                'publishable_key' => array(
                    'title' => __('本番公開鍵', 'woocommerce-payjp'),
                    'type' => 'text'
                ),
            );
        }

        public function get_transaction_url($order) {
            return "https://pay.jp/d/charges/" . $order->get_transaction_id();
        }

        public function payment_fields() {

            if ($this->description) {
                if ($this->testmode) {
                    $this->description .= __('(テストモード)', 'woocommerce-payjp');
                    $this->description = trim($this->description);
                }
                echo wpautop(wp_kses_post($this->description));
            }
            ?>
           <fieldset id="<?php echo 'wc-' . esc_attr( $this->id ) . '-cc-form' ?>" class="wc-credit-card-form wc-payment-form"
                     style="background:transparent;">
               <?php do_action('woocommerce_credit_card_form_start', $this->id); ?>
              <div class="form-row form-row-wide">
                 <label for="payjp_ccNo"><?php echo __('カード番号', 'woocommerce-payjp') ?><span
                          class="required">*</span></label>
                 <input id="payjp_ccNo" name="payjp_ccNo" type="text" autocomplete="off" required />
              </div>
              <div class="form-row form-row-wide">
                 <label for="payjp_cvv"><?php echo __('セキュリティコード', 'woocommerce-payjp') ?> <span class="required">*</span></label>
                 <label><small><?php echo __('※カード裏面に記載されている3〜4桁の番号を入力してください', 'woocommerce-payjp') ?></small></label>
                 <input id="payjp_cvv" name="payjp_cvv" type="password" autocomplete="off" placeholder="<?php echo __('セキュリティコード', 'woocommerce-payjp') ?>" required />
              </div>
              <div class="form-row form-row-wide">
                 <label for="payjp_expdate"><?php echo __('有効期限', 'woocommerce-payjp') ?><span
                          class="required">*</span></label>
                 <label><small><?php echo __('（例）2020 年 4 月なら 04/20', 'woocommerce-payjp') ?></small></label>
                 <input id="payjp_expdate" name="payjp_expdate" type="text" autocomplete="off" placeholder="<?php echo __('月/年', 'woocommerce-payjp') ?>" required />
                 <label></label>
              </div>
              <div class="clear"></div>
               <?php do_action('woocommerce_credit_card_form_end', $this->id); ?>
              <div class="clear"></div>
           </fieldset>
            <?php
        }

        public static function log( $message, $level = 'info' ) {
            if ( self::$log_enabled ) {
                if ( empty( self::$log ) ) {
                    self::$log = wc_get_logger();
                }
                self::$log->log( $level, $message, array( 'source' => 'paypal' ) );
            }
        }

        public function validate_fields() {
            if (empty($_POST['payjp_ccNo'])) {
               wc_add_notice(__('Card number is required', 'error'));
               return false;
            }
            if (empty($_POST['payjp_cvv'])) {
               wc_add_notice(__('CVC number is required', 'error'));
               return false;
            }

            if (empty($_POST['payjp_expdate'])) {
               wc_add_notice(__('Expiry date is required', 'error'));
               return false;
            }
            if (!$this->is_valid_expdate($_POST['payjp_expdate'])) {
                wc_add_notice(__('不正な有効期限年', 'error'));
                return false;
            }
            return true;
        }

        public function is_valid_expdate($expdate) {
            $regex = "/^(01|02|03|04|05|06|07|08|09|10|11|12)\/[2-9][0-9]$/";
            return preg_match($regex, $expdate) ? true : false;
        }

        public function get_exp_month($expdate) {
           return explode("/", $expdate)[0];
        }

        public function get_exp_year($expdate) {
            return "20" . explode("/", $expdate)[1];
        }

        public function process_payment($order_id) {

            $order = wc_get_order($order_id);
            $payjp_params = [
                'card' => [
                    'number' => $_POST['payjp_ccNo'],
                    'cvc' => $_POST['payjp_cvv'],
                    'exp_month' => $this->get_exp_month($_POST['payjp_expdate']),
                    'exp_year' => $this->get_exp_year($_POST['payjp_expdate']),
                ]
            ];

            \Payjp\Payjp::setApiKey($this->private_key);

            try {
                $card = \Payjp\Token::create($payjp_params, $options = ['payjp_direct_token_generate' => 'true']);

                self::log("Token ID: " . $card->id);

                $charge = \Payjp\Charge::create([
                    'amount' => intval($order->get_total()),
                    'card' =>  $card->id,
                    'currency' => $order->get_currency(),
                    'capture' => true
                ]);

                self::log("Charge ID: " . $charge->id);

            } catch(\Exception $e) {
               self::log($e->getMessage(), "error");
               $error_class = get_class($e);
               throw new  $error_class($this->get_error_message($e->getCode()));
            }

            $order->payment_complete($charge->id);
            WC()->cart->empty_cart();

            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order ),
            );

        }

    }

}
