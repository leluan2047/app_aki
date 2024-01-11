<?php return array (
  'DataBackup3' => 
  array (
    'config' => 
    array (
      'name' => 'EC-CUBE4系移行用バックアッププラグイン(3.0系)',
      'code' => 'DataBackup3',
      'version' => '1.0.0',
      'service' => 
      array (
        0 => 'DataBackup3ServiceProvider',
      ),
    ),
    'event' => NULL,
  ),
  'KrAkiCustomizer' => 
  array (
    'config' => 
    array (
      'name' => '着物アキ カスタマイズ',
      'code' => 'KrAkiCustomizer',
      'version' => '1.0.0',
      'service' => 
      array (
        0 => 'KrAkiCustomizerServiceProvider',
      ),
      'event' => 'KrAkiCustomizerEvent',
      'orm.path' => 
      array (
        0 => '/Resource/doctrine',
      ),
    ),
    'event' => 
    array (
      'eccube.event.front.response' => 
      array (
        0 => 
        array (
          0 => 'onEccubeEventFrontResponse',
          1 => 'NORMAL',
        ),
      ),
      'front.product.detail.initialize' => 
      array (
        0 => 
        array (
          0 => 'onFrontProductDetailInitialize',
          1 => 'NORMAL',
        ),
      ),
      'front.product.detail.complete' => 
      array (
        0 => 
        array (
          0 => 'onFrontProductDetailComplete',
          1 => 'NORMAL',
        ),
      ),
      'front.cart.remove.complete' => 
      array (
        0 => 
        array (
          0 => 'onFrontCartRemoveComplete',
          1 => 'NORMAL',
        ),
      ),
      'front.cart.down.complete' => 
      array (
        0 => 
        array (
          0 => 'onFrontCartDownComplete',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.controller.shopping_confirm.before' => 
      array (
        0 => 
        array (
          0 => 'onControllerShoppingConfirmBefore',
          1 => 'FIRST',
        ),
      ),
      'front.shopping.confirm.processing' => 
      array (
        0 => 
        array (
          0 => 'onFrontShoppingConfirmProcessing',
          1 => 'NORMAL',
        ),
      ),
      'front.shopping.index.initialize' => 
      array (
        0 => 
        array (
          0 => 'onFrontShoppingIndexInitialize',
          1 => 'NORMAL',
        ),
      ),
      'front.shopping.payment.initialize' => 
      array (
        0 => 
        array (
          0 => 'onFrontShoppingPaymentInitialize',
          1 => 'NORMAL',
        ),
      ),
      'front.shopping.confirm.initialize' => 
      array (
        0 => 
        array (
          0 => 'onFrontShoppingConfirmInitialize',
          1 => 'NORMAL',
        ),
      ),
      'front.shopping.complete.initialize' => 
      array (
        0 => 
        array (
          0 => 'onFrontShoppingCompleteInitialize',
          1 => 'NORMAL',
        ),
      ),
      'front.product.index.initialize' => 
      array (
        0 => 
        array (
          0 => 'onFrontProductIndexInitialize',
          1 => 'NORMAL',
        ),
      ),
      'front.product.index.search' => 
      array (
        0 => 
        array (
          0 => 'onFrontProductIndexSearch',
          1 => 'NORMAL',
        ),
      ),
      'Product/detail.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderProductDetail',
          1 => 'NORMAL',
        ),
      ),
      'Shopping/index.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderShoppingIndex',
          1 => 'NORMAL',
        ),
      ),
      'Cart/index.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderCartIndex',
          1 => 'NORMAL',
        ),
      ),
      'admin.product.edit.initialize' => 
      array (
        0 => 
        array (
          0 => 'onAdminProductEditInitialize',
          1 => 'NORMAL',
        ),
      ),
      'admin.product.edit.complete' => 
      array (
        0 => 
        array (
          0 => 'onAdminProductEditComplete',
          1 => 'NORMAL',
        ),
      ),
      'admin.order.edit.index.initialize' => 
      array (
        0 => 
        array (
          0 => 'onAdminOrderEditIndexInitialize',
          1 => 'NORMAL',
        ),
      ),
      'Admin/Order/edit.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderAdminOrderEdit',
          1 => 'NORMAL',
        ),
      ),
      'admin.order.edit.index.complete' => 
      array (
        0 => 
        array (
          0 => 'onAdminOrderEditIndexComplete',
          1 => 'NORMAL',
        ),
      ),
    ),
  ),
  'MailMagazine' => 
  array (
    'config' => 
    array (
      'name' => 'MailMagazine',
      'event' => 'MailMagazine',
      'code' => 'MailMagazine',
      'version' => '1.0.0',
      'service' => 
      array (
        0 => 'MailMagazineServiceProvider',
      ),
      'orm.path' => 
      array (
        0 => '/Resource/doctrine',
      ),
      'const' => 
      array (
        'mail_magazine_dir' => '${ROOT_DIR}/app/mail_magazine',
      ),
    ),
    'event' => 
    array (
      'Entry/index.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderEntryIndex',
          1 => 'NORMAL',
        ),
      ),
      'Entry/confirm.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderEntryConfirm',
          1 => 'NORMAL',
        ),
      ),
      'front.entry.index.complete' => 
      array (
        0 => 
        array (
          0 => 'onFrontEntryIndexComplete',
          1 => 'NORMAL',
        ),
      ),
      'Mypage/change.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderMypageChange',
          1 => 'NORMAL',
        ),
      ),
      'front.mypage.change.index.complete' => 
      array (
        0 => 
        array (
          0 => 'onFrontMypageChangeIndexComplete',
          1 => 'NORMAL',
        ),
      ),
      'Admin/Customer/edit.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderAdminCustomerEdit',
          1 => 'NORMAL',
        ),
      ),
      'admin.customer.edit.index.complete' => 
      array (
        0 => 
        array (
          0 => 'onAdminCustomerEditIndexComplete',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.entry.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderEntryBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.controller.entry.after' => 
      array (
        0 => 
        array (
          0 => 'onControllerEntryAfter',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.mypage_change.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderMypageChangeBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.controller.mypage_change.after' => 
      array (
        0 => 
        array (
          0 => 'onControllMypageChangeAfter',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.admin_customer_new.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderAdminCustomerBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.admin_customer_edit.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderAdminCustomerBefore',
          1 => 'NORMAL',
        ),
      ),
    ),
  ),
  'MailTemplateEdit' => 
  array (
    'config' => 
    array (
      'name' => 'メールテンプレート機能拡張プラグイン',
      'event' => 'MailTemplateEdit',
      'code' => 'MailTemplateEdit',
      'version' => '1.0.0',
      'service' => 
      array (
        0 => 'MailTemplateEditServiceProvider',
      ),
      'orm.path' => 
      array (
        0 => '/Resource/doctrine',
      ),
    ),
    'event' => 
    array (
      'eccube.event.app.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderMailTemplate',
          1 => 'NORMAL',
        ),
      ),
    ),
  ),
  'MailTemplateEditor' => 
  array (
    'config' => 
    array (
      'name' => 'メール設定プラグイン',
      'code' => 'MailTemplateEditor',
      'version' => '1.0.0',
      'service' => 
      array (
        0 => 'MailTemplateEditorServiceProvider',
      ),
    ),
    'event' => NULL,
  ),
  'Maker' => 
  array (
    'config' => 
    array (
      'name' => 'Maker',
      'event' => 'MakerEvent',
      'code' => 'Maker',
      'version' => '1.0.0',
      'service' => 
      array (
        0 => 'MakerServiceProvider',
      ),
      'orm.path' => 
      array (
        0 => '/Resource/doctrine',
      ),
    ),
    'event' => 
    array (
      'admin.product.edit.initialize' => 
      array (
        0 => 
        array (
          0 => 'onAdminProductEditInitialize',
          1 => 'NORMAL',
        ),
      ),
      'admin.product.edit.complete' => 
      array (
        0 => 
        array (
          0 => 'onAdminProductEditComplete',
          1 => 'NORMAL',
        ),
      ),
      'Product/detail.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderProductDetail',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.admin_product_product_new.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderAdminProduct',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.admin_product_product_edit.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderAdminProduct',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.product_detail.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderProductDetailBefore',
          1 => 'NORMAL',
        ),
      ),
    ),
  ),
  'MdlPaygent' => 
  array (
    'config' => 
    array (
      'name' => 'EC-CUBEペイジェント決済プラグイン(3.0系)',
      'event' => 'MdlPaymentEvent',
      'code' => 'MdlPaygent',
      'version' => '1.3.1',
      'service' => 
      array (
        0 => 'PaymentServiceProvider',
      ),
      'const' => 
      array (
        'MDL_PAYGENT_CODE' => 'MdlPaygent',
        'MDL_PAYGENT_NAME' => 'ペイジェント決済プラグイン',
        'PAYGENT_LOG_PATH_LINK' => '/app/log/paygent.log',
        'PAYGENT_LOG_PATH' => '/app/log/paygent_cube.log',
        'TRANSACTION_ID_NAME' => 'transactionid',
        'PAY_PAYMENT_LUMP_SUM' => 10,
        'PAY_PAYGENT_INSTALL' => 61,
        'PAY_PAYGENT_REVOLVING_CREDIT' => 80,
        'PAY_PAYGENT_BONUS_LUMP_SUM' => 23,
        'PaygentB2BModule__TELEGRAM_KEY_LENGTH' => 30,
        'PaygentB2BModule__TELEGRAM_VALUE_LENGTH' => 102400,
        'PaygentB2BModule__TELEGRAM_LENGTH' => 102400,
        'PaygentB2BModule__TELEGRAM_LENGTH_FILE' => 10485760,
        'PaygentB2BModule__CONNECT_ID_KEY' => 'connect_id',
        'PaygentB2BModule__CONNECT_PASSWORD_KEY' => 'connect_password',
        'PaygentB2BModule__TELEGRAM_KIND_KEY' => 'telegram_kind',
        'PaygentB2BModule__LIMIT_COUNT_KEY' => 'limit_count',
        'PaygentB2BModule__DATA_KEY' => 'data',
        'PaygentB2BModule__RESULT_STATUS_ERROR' => '1',
        'PaygentB2BModule__RESPONSE_CODE_9003' => '9003',
        'PaygentB2BModule__TELEGRAM_KIND_FILE_PAYMENT_RES' => '201',
        'PaygentB2BModuleResources__PROPERTIES_FILE_NAME' => 'modenv_properties.php',
        'PaygentB2BModuleResources__TELEGRAM_KIND_SEPARATOR' => ',',
        'PaygentB2BModuleResources__TELEGRAM_KIND_FIRST_CHARS' => 2,
        'PaygentB2BModuleResources__CLIENT_FILE_PATH' => 'paygentB2Bmodule.client_file_path',
        'PaygentB2BModuleResources__NOT_USE_CLIENT_CERT' => 'paygentB2Bmodule.not_use_client_cert',
        'PaygentB2BModuleResources__CA_FILE_PATH' => 'paygentB2Bmodule.ca_file_path',
        'PaygentB2BModuleResources__NOT_USE_CA_CERT' => 'paygentB2Bmodule.not_use_ca_cert',
        'PaygentB2BModuleResources__PROXY_SERVER_NAME' => 'paygentB2Bmodule.proxy_server_name',
        'PaygentB2BModuleResources__PROXY_SERVER_IP' => 'paygentB2Bmodule.proxy_server_ip',
        'PaygentB2BModuleResources__PROXY_SERVER_PORT' => 'paygentB2Bmodule.proxy_server_port',
        'PaygentB2BModuleResources__DEFAULT_ID' => 'paygentB2Bmodule.default_id',
        'PaygentB2BModuleResources__DEFAULT_PASSWORD' => 'paygentB2Bmodule.default_password',
        'PaygentB2BModuleResources__TIMEOUT_VALUE' => 'paygentB2Bmodule.timeout_value',
        'PaygentB2BModuleResources__LOG_OUTPUT_PATH' => 'paygentB2Bmodule.log_output_path',
        'PaygentB2BModuleResources__SELECT_MAX_CNT' => 'paygentB2Bmodule.select_max_cnt',
        'PaygentB2BModuleResources__TELEGRAM_KIND_REFS' => 'paygentB2Bmodule.telegram_kind.ref',
        'PaygentB2BModuleResources__URL_COMM' => 'paygentB2Bmodule.url.',
        'PaygentB2BModuleResources__DEBUG_FLG' => 'paygentB2Bmodule.debug_flg',
        'PaygentB2BModuleConnectException__serialVersionUID' => 1,
        'PaygentB2BModuleConnectException__MODULE_PARAM_REQUIRED_ERROR' => 'E02001',
        'PaygentB2BModuleConnectException__TEREGRAM_PARAM_REQUIRED_ERROR' => 'E02002',
        'PaygentB2BModuleConnectException__TEREGRAM_PARAM_OUTSIDE_ERROR' => 'E02003',
        'PaygentB2BModuleConnectException__CERTIFICATE_ERROR' => 'E02004',
        'PaygentB2BModuleConnectException__KS_CONNECT_ERROR' => 'E02005',
        'PaygentB2BModuleConnectException__RESPONSE_TYPE_ERROR' => 'E02007',
        'PaygentB2BModuleException__serialVersionUID' => 1,
        'PaygentB2BModuleException__RESOURCE_FILE_NOT_FOUND_ERROR' => 'E01001',
        'PaygentB2BModuleException__RESOURCE_FILE_REQUIRED_ERROR' => 'E01002',
        'PaygentB2BModuleException__OTHER_ERROR' => 'E01901',
        'PaygentB2BModuleException__CSV_OUTPUT_ERROR' => 'E01004',
        'PaygentB2BModuleException__FILE_PAYMENT_ERROR' => 'E01005',
        'CSVTokenizer__DEF_SEPARATOR' => ',',
        'CSVTokenizer__DEF_ITEM_ENVELOPE' => '"',
        'CSVTokenizer__NO_ITEM_ENVELOPE' => 'chr(0)',
        'CSVWriter__ENCODING_SJIS' => 'Shift_JIS',
        'CSVWriter__ENCODING_EUC' => 'EUC_JP',
        'CSVWriter__ENCODING_MS932' => 'SJIS-win',
        'CSVWriter__WINDOWS_NEWLINE' => '
',
        'CSVWriter__UNIX_NEWLINE' => '
',
        'CSVWriter__MAC_NEWLINE' => '',
        'HttpsRequestSender__CURLE_COULDNT_CONNECT' => 7,
        'HttpsRequestSender__CURLE_SSL_CERTPROBLEM' => 58,
        'HttpsRequestSender__CURLE_SSL_CACERT' => 60,
        'HttpsRequestSender__CURLE_SSL_CACERT_BADFILE' => 77,
        'HttpsRequestSender__CURLE_HTTP_RETURNED_ERROR' => 22,
        'HttpsRequestSender__POST' => 'POST',
        'HttpsRequestSender__HTTP' => 'HTTP',
        'HttpsRequestSender__HTTP_1_0' => 'HTTP/1.0',
        'HttpsRequestSender__HTTP_1_0_200' => 'HTTP/1.0 200',
        'HttpsRequestSender__HTTP_SUCCESS' => 200,
        'HttpsRequestSender__HTTP_PARTIAL_CONTENT' => 206,
        'HttpsRequestSender__TELEGRAM_LENGTH' => 10240,
        'HttpsRequestSender__DEFAULT_PORT' => 443,
        'HttpsRequestSender__CRLF' => '
',
        'HttpsRequestSender__DEFAULT_ENCODING' => 'SJIS-win',
        'HttpsRequestSender__HTTP_STATUS_INIT_VALUE' => -1,
        'HttpsRequestSender__REGEXPSTATUS_LEN' => 3,
        'HttpsRequestSender__CONTENT_LENGTH' => 'Content-Length',
        'HttpsRequestSender__USER_AGENT' => 'User-Agent',
        'HttpsRequestSender__CONTENT_TYPE' => 'Content-Type=application/x-www-form-urlencoded',
        'HttpsRequestSender__HTTP_ENCODING' => 'charset=Windows-31J',
        'HttpsRequestSender__MASK_STRING' => 'X',
        'FilePaymentResponseDataImpl__LINENO_HEADER' => '1',
        'FilePaymentResponseDataImpl__LINE_RECORD_DIVISION' => 0,
        'FilePaymentResponseDataImpl__LINE_HEADER_RESULT' => 6,
        'FilePaymentResponseDataImpl__LINE_HEADER_RESPONSE_CODE' => 7,
        'FilePaymentResponseDataImpl__LINE_HEADER_RESPONSE_DETAIL' => 8,
        'FilePaymentResponseDataImpl__LINE_SEPARATOR' => '
',
        'PaymentResponseDataImpl__PROPERTIES_REGEX' => '=',
        'PaymentResponseDataImpl__PROPERTIES_REGEX_COUNT' => 2,
        'PaymentResponseDataImpl__LINE_SEPARATOR' => '
',
        'ResponseData__RESULT' => 'result',
        'ResponseData__RESPONSE_CODE' => 'response_code',
        'ResponseData__RESPONSE_DETAIL' => 'response_detail',
        'ResponseData__HTML_ITEM' => '_html',
        'ReferenceResponseDataImpl__LINENO_HEADER' => '1',
        'ReferenceResponseDataImpl__LINENO_DATA_HEADER' => '2',
        'ReferenceResponseDataImpl__LINENO_DATA' => '3',
        'ReferenceResponseDataImpl__LINENO_TRAILER' => '4',
        'ReferenceResponseDataImpl__LINE_RECORD_DIVISION' => 0,
        'ReferenceResponseDataImpl__LINE_HEADER_RESULT' => 1,
        'ReferenceResponseDataImpl__LINE_HEADER_RESPONSE_CODE' => 2,
        'ReferenceResponseDataImpl__LINE_HEADER_RESPONSE_DETAIL' => 3,
        'ReferenceResponseDataImpl__LINE_TRAILER_DATA_COUNT' => 1,
        'ReferenceResponseDataImpl__LINE_SEPARATOR' => '
',
        'SETTLEMENT_LINK' => 1,
        'SETTLEMENT_MODULE' => 2,
        'SETTLEMENT_MIX' => 3,
        'INVOICE_SEND_TYPE_SEPARATE' => 2,
        'INVOICE_SEND_TYPE_INCLUDE' => 3,
        'PAY_PAYGENT_CREDIT' => '1',
        'PAY_PAYGENT_CONVENI_NUM' => '2',
        'PAY_PAYGENT_CONVENI_CALL' => '3',
        'PAY_PAYGENT_ATM' => '4',
        'PAY_PAYGENT_BANK' => '5',
        'PAY_PAYGENT_CAREER' => '6',
        'PAY_PAYGENT_EMONEY' => '7',
        'PAY_PAYGENT_YAHOOWALLET' => '8',
        'PAY_PAYGENT_LINK' => '50',
        'PAY_PAYGENT_VIRTUAL_ACCOUNT' => '9',
        'PAY_PAYGENT_LATER_PAYMENT' => '10',
        'PAYGENT_ATM' => '010',
        'PAYGENT_CREDIT' => '020',
        'PAYGENT_CREDIT_PROCESSING' => '0201',
        'PAYGENT_AUTH_CANCEL' => '021',
        'PAYGENT_CARD_COMMIT' => '022',
        'PAYGENT_CARD_COMMIT_CANCEL' => '023',
        'PAYGENT_CARD_COMMIT_REVICE' => '029',
        'PAYGENT_CARD_COMMIT_REVICE_PROCESSING' => '0291',
        'PAYGENT_CARD_3D' => '024',
        'PAYGENT_CARD_STOCK_SET' => '025',
        'PAYGENT_CARD_STOCK_DEL' => '026',
        'PAYGENT_CARD_STOCK_GET' => '027',
        'PAYGENT_CONVENI_NUM' => '030',
        'PAYGENT_CONVENI_CALL' => '040',
        'PAYGENT_BANK' => '060',
        'PAYGENT_CAREER' => '100',
        'PAYGENT_CAREER_COMMIT' => '101',
        'PAYGENT_CAREER_COMMIT_CANCEL' => '102',
        'PAYGENT_CAREER_COMMIT_REVICE' => '103',
        'PAYGENT_CAREER_COMMIT_AUTH' => '104',
        'PAYGENT_EMONEY' => '150',
        'PAYGENT_EMONEY_COMMIT_CANCEL' => '152',
        'PAYGENT_EMONEY_COMMIT_REVICE' => '153',
        'PAYGENT_YAHOOWALLET' => '160',
        'PAYGENT_YAHOOWALLET_COMMIT' => '161',
        'PAYGENT_YAHOOWALLET_COMMIT_CANCEL' => '162',
        'PAYGENT_YAHOOWALLET_COMMIT_REVICE' => '163',
        'PAYGENT_VIRTUAL_ACCOUNT' => '070',
        'PAYGENT_LATER_PAYMENT' => '220',
        'PAYGENT_LATER_PAYMENT_CANCEL' => '221',
        'PAYGENT_LATER_PAYMENT_CLEAR' => '222',
        'PAYGENT_LATER_PAYMENT_REDUCTION' => '223',
        'PAYGENT_LATER_PAYMENT_BILL_REISSUE' => '224',
        'PAYGENT_LATER_PAYMENT_PRINT' => '225',
        'PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_NG' => '220_11',
        'PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_RESERVE' => '220_12',
        'PAYGENT_LATER_PAYMENT_ST_AUTHORIZED_BEFORE_PRINT' => '220_19',
        'PAYGENT_LATER_PAYMENT_ST_AUTHORIZED' => '220_20',
        'PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_CANCEL' => '220_32',
        'PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_EXPIRE' => '220_33',
        'PAYGENT_LATER_PAYMENT_ST_CLEAR_REQ_FIN' => '220_35',
        'PAYGENT_LATER_PAYMENT_ST_SALES_RESERVE' => '220_36',
        'PAYGENT_LATER_PAYMENT_ST_CLEAR' => '220_40',
        'PAYGENT_LATER_PAYMENT_ST_CLEAR_SALES_CANCEL_INVALIDITY' => '220_41',
        'PAYGENT_LATER_PAYMENT_ST_SALES_CANCEL' => '220_60',
        'PAYGENT_CAREER_D' => '100_1',
        'PAYGENT_CAREER_A' => '100_2',
        'PAYGENT_CAREER_S' => '100_3',
        'PAYGENT_CAREER_AUTH_D' => '104_1',
        'PAYGENT_CAREER_AUTH_A' => '104_2',
        'PAYGENT_EMONEY_W' => '150_1',
        'PAYGENT_LINK' => 'link',
        'PAYGENT_REF' => '091',
        'PAYMENT_TYPE_ATM' => '01',
        'PAYMENT_TYPE_CREDIT' => '02',
        'PAYMENT_TYPE_CONVENI_NUM' => '03',
        'PAYMENT_TYPE_BANK' => '05',
        'PAYMENT_TYPE_CAREER' => '06',
        'PAYMENT_TYPE_EMONEY' => '11',
        'PAYMENT_TYPE_YAHOOWALLET' => '12',
        'PAYMENT_TYPE_VIRTUAL_ACCOUNT' => '07',
        'PAYMENT_TYPE_LATER_PAYMENT' => '15',
        'STATUS_PRE_REGISTRATION' => '10',
        'STATUS_NG_AUTHORITY' => '11',
        'STATUS_PAYMENT_EXPIRED' => '12',
        'STATUS_3DSECURE_INTERRUPTION' => '13',
        'STATUS_REGISTRATION_SUSPENDED' => '15',
        'STATUS_PAYMENT_INVALIDITY_NO_CLEAR' => '16',
        'STATUS_AUTHORITY_OK' => '20',
        'STATUS_AUTHORITY_COMPLETED' => '21',
        'STATUS_AUTHORITY_CANCELED' => '32',
        'STATUS_AUTHORITY_EXPIRED' => '33',
        'STATUS_PENDING_SALES' => '36',
        'STATUS_NO_PENDING' => '37',
        'STATUS_PRE_CLEARED' => '40',
        'STATUS_PRE_CLEARED_EXPIRATION_CANCELLATION_SALES' => '41',
        'STATUS_PRELIMINARY_PRE_DETECTION' => '43',
        'STATUS_COMPLETE_CLEARED' => '44',
        'STATUS_PRE_SALES_CANCELLATION' => '60',
        'STATUS_PRELIMINARY_CANCELLATION' => '61',
        'STATUS_COMPLETE_CANCELLATION' => '62',
        'STATUS_REQUESTED' => '10',
        'STATUS_AUTHORIZE_NG' => '11',
        'STATUS_AUTHORIZE_RESERVE' => '12',
        'STATUS_AUTHORIZED_BEFORE_PRINT' => '19',
        'STATUS_AUTHORIZED' => '20',
        'STATUS_AUTHORIZE_CANCEL' => '32',
        'STATUS_AUTHORIZE_EXPIRE' => '33',
        'STATUS_CLEAR_REQ_FIN' => '35',
        'STATUS_SALES_RESERVE' => '36',
        'STATUS_CLEAR' => '40',
        'STATUS_CLEAR_SALES_CANCEL_INVALIDITY' => '41',
        'STATUS_SALES_CANCEL' => '60',
        'PAYGENT_REF_LOOP' => 1000,
        'PAYGENT_CART_SESS_KEY' => '_paygent_cart_sess_key_',
        'CHARGE_MAX' => 500000,
        'SEVEN_CHARGE_MAX' => 300000,
        'TELEGRAM_VERSION' => '1.0',
        'CODE_SEVENELEVEN' => '00C001',
        'CODE_LOWSON' => '00C002',
        'CODE_MINISTOP' => '00C004',
        'CODE_FAMILYMART' => '00C005',
        'CODE_SUNKUS' => '00C006',
        'CODE_CIRCLEK' => '00C007',
        'CODE_YAMAZAKI' => '00C014',
        'CODE_SEICOMART' => '00C016',
        'PC_MOBILE_TYPE_PC' => '0',
        'PC_MOBILE_TYPE_SMARTPHONE' => '4',
        'CAREER_MOBILE_TYPE_DOCOMO' => '1',
        'CAREER_MOBILE_TYPE_AU' => '2',
        'CAREER_MOBILE_TYPE_SOFTBANK' => '3',
        'CAREER_TYPE_AU' => '4',
        'CAREER_TYPE_DOCOMO' => '5',
        'CAREER_TYPE_SOFTBANK' => '6',
        'TEL_HOME' => '1',
        'TEL_CALL' => '2',
        'TEL_DORMITORY' => '3',
        'TEL_MOBILE' => '5',
        'EMONEY_TYPE_WEBMONEY' => '1',
        'EMONEY_TYPE_CHOCOMU' => '2',
        'NUMBERING_TYPE_CYCLE' => 0,
        'NUMBERING_TYPE_FIX' => 1,
        'RESULT_GET_TYPE_WAIT' => 0,
        'RESULT_GET_TYPE_NO_WAIT' => 1,
        'EXAM_RESULT_NOTIFICATION_TYPE_AUTO' => 0,
        'EXAM_RESULT_NOTIFICATION_TYPE_MANUAL' => 1,
        'AUTO_CANCEL_TYPE_WAIT' => 0,
        'AUTO_CANCEL_TYPE_NO_WAIT' => 1,
        'CLIENT_REASON_CODE_DEFAULT' => '',
        'CLIENT_REASON_CODE_BILL_LOSS' => '01',
        'CLIENT_REASON_CODE_BILL_NO_DELIVERY' => '02',
        'CLIENT_REASON_CODE_MOVE' => '03',
        'CLIENT_REASON_CODE_OTHER' => '99',
        'CARRIERS_COMPANY_CODE_DEFAULT' => '',
        'CARRIERS_COMPANY_CODE_SAGAWA' => '11',
        'CARRIERS_COMPANY_CODE_YAMATO' => '12',
        'CARRIERS_COMPANY_CODE_SEINO' => '14',
        'CARRIERS_COMPANY_CODE_REGISTERED' => '15',
        'CARRIERS_COMPANY_CODE_YUPACK' => '16',
        'CARRIERS_COMPANY_CODE_FUKUTSU' => '18',
        'CARRIERS_COMPANY_CODE_SPECIFY_TIME' => '28',
        'CARRIERS_COMPANY_CODE_ECOHAI' => '27',
        'PAYGENT_BANK_STEXT_LEN' => '12',
        'PAYGENT_CONVENI_STEXT_LEN' => '14',
        'PAYGENT_CONVENI_MTEXT_LEN' => '20',
        'PAYGENT_TEL_ITEM_LEN' => 11,
        'PAYGENT_S_TEL_ITEM_LEN' => 4,
        'PAYGENT_LINK_STEXT_LEN' => '12',
        'PAYGENT_VIRTUAL_ACCOUNT_STEXT_LEN' => '48',
        'PAYGENT_VIRTUAL_ACCOUNT_MTEXT_LEN' => '100',
        'OPTION_INVOICE_SEND_TYPE_SEPARATE' => '2',
        'OPTION_INVOICE_SEND_TYPE_INCLUDE' => '3',
        'log_realfile' => '/PATH/TO/WEB_ROOT/data/logs/site.log',
        'max_log_quantity' => 5,
        'max_log_size' => '1000000',
      ),
      'orm.path' => 
      array (
        0 => '/Resource/doctrine',
      ),
    ),
    'event' => 
    array (
      'eccube.event.render.shopping.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderShoppingBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.controller.shopping_confirm.before' => 
      array (
        0 => 
        array (
          0 => 'onControllerShoppingConfirmBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.shopping_complete.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderShoppingCompleteBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.admin_order.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderAdminOrderBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.admin_order_page.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderAdminOrderBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.admin_order_edit.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderAdminOrderEditBefore',
          1 => 'NORMAL',
        ),
      ),
    ),
  ),
  'PlgExpandProductColumns' => 
  array (
    'config' => 
    array (
      'name' => '商品情報追加プラグイン',
      'code' => 'PlgExpandProductColumns',
      'version' => '1.0.1',
      'orm.path' => 
      array (
        0 => '/Resource/doctrine',
      ),
      'service' => 
      array (
        0 => 'PlgExpandProductColumnsServiceProvider',
      ),
      'event' => 'Event',
    ),
    'event' => 
    array (
      'eccube.event.render.admin_product_product_edit.before' => 
      array (
        0 => 
        array (
          0 => 'addContentOnProductEdit',
          1 => 'NORMAL',
        ),
        1 => 
        array (
          0 => 'saveExColValue',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.admin_product_product_new.before' => 
      array (
        0 => 
        array (
          0 => 'addContentOnProductEdit',
          1 => 'NORMAL',
        ),
        1 => 
        array (
          0 => 'saveExColValue',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.controller.product_detail.before' => 
      array (
        0 => 
        array (
          0 => 'setExpandColumns',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.controller.product_list.before' => 
      array (
        0 => 
        array (
          0 => 'setExpandColumns',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.controller.admin_product.before' => 
      array (
        0 => 
        array (
          0 => 'setExpandColumns',
          1 => 'NORMAL',
        ),
      ),
      'Admin/Product/product.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderAdminProductNew',
          1 => 'NORMAL',
        ),
      ),
      'Admin/Product/csv_product.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderAdminCsvImport',
          1 => 'NORMAL',
        ),
      ),
    ),
  ),
  'Point' => 
  array (
    'config' => 
    array (
      'name' => 'Pointプラグイン',
      'code' => 'Point',
      'version' => '1.0.0',
      'event' => 'PointEvent',
      'service' => 
      array (
        0 => 'PointServiceProvider',
      ),
      'orm.path' => 
      array (
        0 => '/Resource/doctrine',
      ),
    ),
    'event' => 
    array (
      'admin.product.edit.initialize' => 
      array (
        0 => 
        array (
          0 => 'onAdminProductEditInitialize',
          1 => 'NORMAL',
        ),
      ),
      'admin.product.edit.complete' => 
      array (
        0 => 
        array (
          0 => 'onAdminProductEditComplete',
          1 => 'NORMAL',
        ),
      ),
      'admin.customer.edit.index.initialize' => 
      array (
        0 => 
        array (
          0 => 'onAdminCustomerEditIndexInitialize',
          1 => 'NORMAL',
        ),
      ),
      'admin.customer.edit.index.complete' => 
      array (
        0 => 
        array (
          0 => 'onAdminCustomerEditIndexComplete',
          1 => 'NORMAL',
        ),
      ),
      'admin.order.edit.index.initialize' => 
      array (
        0 => 
        array (
          0 => 'onAdminOrderEditIndexInitialize',
          1 => 'NORMAL',
        ),
      ),
      'admin.order.edit.index.complete' => 
      array (
        0 => 
        array (
          0 => 'onAdminOrderEditIndexComplete',
          1 => 'NORMAL',
        ),
      ),
      'admin.order.delete.complete' => 
      array (
        0 => 
        array (
          0 => 'onAdminOrderDeleteComplete',
          1 => 'NORMAL',
        ),
      ),
      'admin.order.mail.index.complete' => 
      array (
        0 => 
        array (
          0 => 'onAdminOrderMailIndexComplete',
          1 => 'NORMAL',
        ),
      ),
      'admin.order.mail.mail.all.complete' => 
      array (
        0 => 
        array (
          0 => 'onAdminOrderMailIndexComplete',
          1 => 'NORMAL',
        ),
      ),
      'front.shopping.confirm.processing' => 
      array (
        0 => 
        array (
          0 => 'onFrontShoppingConfirmProcessing',
          1 => 'NORMAL',
        ),
      ),
      'service.shopping.notify.complete' => 
      array (
        0 => 
        array (
          0 => 'onServiceShoppingNotifyComplete',
          1 => 'NORMAL',
        ),
      ),
      'Shopping/complete.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderShoppingComplete',
          1 => 'NORMAL',
        ),
      ),
      'front.shopping.delivery.complete' => 
      array (
        0 => 
        array (
          0 => 'onFrontChangeTotal',
          1 => 'NORMAL',
        ),
      ),
      'front.shopping.payment.complete' => 
      array (
        0 => 
        array (
          0 => 'onFrontChangeTotal',
          1 => 'NORMAL',
        ),
      ),
      'front.shopping.shipping.complete' => 
      array (
        0 => 
        array (
          0 => 'onFrontChangeTotal',
          1 => 'NORMAL',
        ),
      ),
      'front.shopping.shipping.edit.complete' => 
      array (
        0 => 
        array (
          0 => 'onFrontChangeTotal',
          1 => 'NORMAL',
        ),
      ),
      'Admin/Order/edit.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderAdminOrderEdit',
          1 => 'NORMAL',
        ),
      ),
      'Admin/Order/mail_confirm.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderAdminOrderMailConfirm',
          1 => 'NORMAL',
        ),
      ),
      'Admin/Order/mail_all_confirm.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderAdminOrderMailConfirm',
          1 => 'NORMAL',
        ),
      ),
      'Mypage/index.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderMyPageIndex',
          1 => 'NORMAL',
        ),
      ),
      'Shopping/index.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderShoppingIndex',
          1 => 'NORMAL',
        ),
      ),
      'Product/detail.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderProductDetail',
          1 => 'NORMAL',
        ),
      ),
      'Cart/index.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderCart',
          1 => 'NORMAL',
        ),
      ),
      'Mypage/history.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderHistory',
          1 => 'NORMAL',
        ),
      ),
      'mail.order' => 
      array (
        0 => 
        array (
          0 => 'onMailOrderComplete',
          1 => 'NORMAL',
        ),
      ),
      'mail.admin.order' => 
      array (
        0 => 
        array (
          0 => 'onMailOrderComplete',
          1 => 'NORMAL',
        ),
      ),
    ),
  ),
);