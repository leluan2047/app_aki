<?php
### Client Certificate File        ###
### �N���C�A���g�ؖ����t�@�C���p�X ###
paygentB2Bmodule.client_file_path=/var/www/vhosts/kr-aki.co.jp/shared/Plugin/MdlPaygent/jp/co/ks/merchanttool/connectmodule/system/20716-20161027_client_cert.pem

### Trusted Server Certificate ###
### �F�؍ς݂�CA�t�@�C���p�X   ###
paygentB2Bmodule.ca_file_path=/var/www/vhosts/kr-aki.co.jp/shared/Plugin/MdlPaygent/jp/co/ks/merchanttool/connectmodule/system/curl-ca-bundle.crt


### Proxy Server Settings ( Edit them when connections are proxied) ###
### �v���L�V�T�[�o�[�ݒ�i�v���L�V�T�[�o�[���g�p����ꍇ�̂ݐݒ�j  ###
paygentB2Bmodule.proxy_server_name=
paygentB2Bmodule.proxy_server_ip=
paygentB2Bmodule.proxy_server_port=0

### Default ID/Password (Used when these values are not specified within programs) ###
### �ڑ�ID�A�ڑ��p�X���[�h���ݒ肳��Ȃ��ꍇ�Ɏg�p�����f�t�H���g�l�i�󔒉j     ###
paygentB2Bmodule.default_id=
paygentB2Bmodule.default_password=

### Timeout value in second ###
### �^�C���A�E�g�l�i�b�j     ###
paygentB2Bmodule.timeout_value=35

### Program Log File     ###
### ���O�t�@�C���o�̓p�X ###
paygentB2Bmodule.log_output_path=/var/www/vhosts/kr-aki.jp/app/log/{paygent}.log

###�f�o�b�O�I�v�V����###
# 1:���N�G�X�g/���X�|���X�����O�o��
# 0:�G���[���̂ݏo��
# ���{�ԉғ����͕K��0��ݒ肵�Ă�������
paygentB2Bmodule.debug_flg=0

#!!!  DO NOT EDIT BELOW THIS LINE   !!!
#!!! �ȉ��̒l�͕ҏW���Ȃ��ł������� !!!

###�ő�Ɖ���i2000�����y�C�W�F���g�V�X�e���̍ő�l�Ȃ̂ł���ȏ�̒l�͖����j###
paygentB2Bmodule.select_max_cnt=2000

###CSV�o�͑Ώ�###
paygentB2Bmodule.telegram_kind.ref=027,090
###ATM����URL###
paygentB2Bmodule.url.01=https://module.paygent.co.jp/n/atm/request
###�N���W�b�g�J�[�h����URL1###
paygentB2Bmodule.url.02=https://module.paygent.co.jp/n/card/request
###�N���W�b�g�J�[�h����URL2###
paygentB2Bmodule.url.11=https://module.paygent.co.jp/n/card/request
###�N���W�b�g�J�[�h����(���ʉ�)URL###
paygentB2Bmodule.url.18=https://module.paygent.co.jp/n/card/request
###�N���W�b�g�J�[�h����(�[���ǎ�)URL###
paygentB2Bmodule.url.19=https://module.paygent.co.jp/n/card/request
###�N���W�b�g�J�[�h����URL(�p���ۋ��p)###
paygentB2Bmodule.url.28=https://module.paygent.co.jp/n/card/request
###�N���W�b�g�J�[�h����URL(�p���ۋ��Ɖ�p)###
paygentB2Bmodule.url.096=https://module.paygent.co.jp/n/card/request
###�R���r�j�ԍ���������URL###
paygentB2Bmodule.url.03=https://module.paygent.co.jp/n/conveni/request
###�R���r�j���[��������URL###
paygentB2Bmodule.url.04=https://module.paygent.co.jp/n/conveni/request_print
###��s�l�b�g����URL###
paygentB2Bmodule.url.05=https://module.paygent.co.jp/n/bank/request
###��s�l�b�g����ASPURL###
paygentB2Bmodule.url.06=https://module.paygent.co.jp/n/bank/requestasp
###���z��������URL###
paygentB2Bmodule.url.07=https://module.paygent.co.jp/n/virtualaccount/request
###���Ϗ��Ɖ�URL###
paygentB2Bmodule.url.09=https://module.paygent.co.jp/n/ref/request
###���Ϗ�񍷕��Ɖ�URL###
paygentB2Bmodule.url.091=https://module.paygent.co.jp/n/ref/paynotice
###�L�����A�p���ۋ������Ɖ�URL###
paygentB2Bmodule.url.093=https://module.paygent.co.jp/n/ref/runnotice
###���Ϗ��Ɖ�URL###
paygentB2Bmodule.url.094=https://module.paygent.co.jp/n/ref/paymentref
###�g�уL�����A����URL###
paygentB2Bmodule.url.10=https://module.paygent.co.jp/n/c/request
###�g�уL�����A����URL�i�p���ۋ��p�j###
paygentB2Bmodule.url.12=https://module.paygent.co.jp/n/c/request
###�t�@�C������URL###
paygentB2Bmodule.url.20=https://module.paygent.co.jp/n/o/requestdata
###�d�q�}�l�[����URL###
paygentB2Bmodule.url.15=https://module.paygent.co.jp/n/emoney/request
###PayPal����URL###
paygentB2Bmodule.url.13=https://module.paygent.co.jp/n/paypal/request
###�J�[�h�ԍ��Ɖ�URL###
paygentB2Bmodule.url.095=https://module.paygent.co.jp/n/ref/cardnoref
###�㕥������URL###
paygentB2Bmodule.url.22=https://module.paygent.co.jp/n/later/request
###�����U�֌���URL###
paygentB2Bmodule.url.26=https://module.paygent.co.jp/n/accounttransfer/request
###�l�b�g�����U�֎�tURL###
paygentB2Bmodule.url.263=https://module.paygent.co.jp/n/accounttransfer/receipt
###�l�b�g�����U�֎�tURL###
paygentB2Bmodule.url.264=https://module.paygent.co.jp/n/accounttransfer/receipt
###�y�VID����URL###
paygentB2Bmodule.url.27=https://module.paygent.co.jp/n/rakutenid/request
###JCBPREMO����URL###
paygentB2Bmodule.url.29=https://module.paygent.co.jp/n/jcbpremo/request
?>