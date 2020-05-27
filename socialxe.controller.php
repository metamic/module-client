<?php
	/**
	 * @class  socialxeController
     * @author CONORY (https://xe.conory.com)
	 * @brief Controller class of socialxe modules
	 */
	class socialxeController extends socialxe
	{
		/**
		 * @brief Initialization
		 */
		function init()
		{
		}
		
		/**
		 * @brief �̸��� Ȯ��
		 */
		function procSocialxeConfirmMail()
		{
			if(!$_SESSION['socialxe_confirm_email'])
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}
			
			if(!$email_address = Context::get('email_address'))
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}
			
			if(getModel('member')->getMemberSrlByEmailAddress($email_address))
			{
				$error = 'msg_exists_email_address';
			}
			
			$saved = $_SESSION['socialxe_confirm_email'];
			$mid = $_SESSION['socialxe_current']['mid'];
			$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', '');
			
			if(!$error)
			{
				if(!$oLibrary = $this->getLibrary($saved['service']))
				{
					return new BaseObject(-1, 'msg_invalid_request');
				}
				
				$oLibrary->set($saved);
				$oLibrary->setEmail($email_address);
				
				$output = $this->LoginSns($oLibrary);
				if(!$output->toBool())
				{
					$error = $output->getMessage();
					$errorCode = $output->getError();
				}
			}
			
			// ����
			if($error)
			{
				$msg = $error;
				
				if($errorCode == -12)
				{
					Context::set('xe_validator_id', '');
					$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', 'dispMemberLoginForm');
				}
				else
				{
					$_SESSION['tmp_socialxe_confirm_email'] = $_SESSION['socialxe_confirm_email'];
					
					$this->setError(-1);
					$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', 'dispSocialxeConfirmMail');
				}
			}
			
			unset($_SESSION['socialxe_confirm_email']);
			
			// �α� ���
			$info = new stdClass;
			$info->msg = $msg;
			$info->sns = $saved['service'];
			getModel('socialxe')->logRecord($this->act, $info);
			
			if($msg)
			{
				$this->setMessage($msg);
			}
			
			if(!$this->getRedirectUrl())
			{
				$this->setRedirectUrl($redirect_url);
			}
		}
		
		/**
		 * @brief �߰����� �Է�
		 */
		function procSocialxeInputAddInfo()
		{
			if(!$_SESSION['socialxe_input_add_info'])
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}
			
			$saved = $_SESSION['socialxe_input_add_info'];
			$mid = $_SESSION['socialxe_current']['mid'];
			$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', '');
			
			$signupForm = array();
			
			// �ʼ� �߰� ������
			if(in_array('require_add_info', $this->config->sns_input_add_info))
			{
				foreach(getModel('member')->getMemberConfig()->signupForm as $no => $formInfo)
				{
					if(!$formInfo->required || $formInfo->isDefaultForm)
					{
						continue;
					}
					
					$signupForm[] = $formInfo->name;
				}
			}
			
			// ���̵� ��
			if(in_array('user_id', $this->config->sns_input_add_info))
			{
				$signupForm[] = 'user_id';
				
				if(getModel('member')->getMemberSrlByUserID(Context::get('user_id')))
				{
					$error = 'msg_exists_user_id';
				}
			}
			
			// �г��� ��
			if(in_array('nick_name', $this->config->sns_input_add_info))
			{
				$signupForm[] = 'nick_name';
				
				if(getModel('member')->getMemberSrlByNickName(Context::get('nick_name')))
				{
					$error = 'msg_exists_nick_name';
				} 
			}
			
			// ��� ����
			if(in_array('agreement', $this->config->sns_input_add_info))
			{
				$signupForm[] = 'accept_agreement';
			}
			
			// �߰� ���� ����
			$add_data = array();
			foreach($signupForm as $val)
			{
				$add_data[$val] = Context::get($val);
			}
			
			if(!$error)
			{
				if(!$oLibrary = $this->getLibrary($saved['service']))
				{
					return new BaseObject(-1, 'msg_invalid_request');
				}
				
				$_SESSION['socialxe_input_add_info_data'] = $add_data;
				
				$oLibrary->set($saved);
				$output = $this->LoginSns($oLibrary);
				
				if(!$output->toBool())
				{
					$error = $output->getMessage();
				}
			}
			
			// ����
			if($error)
			{
				$msg = $error;
				$this->setError(-1);
				$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', 'dispSocialxeInputAddInfo');
				
				$_SESSION['tmp_socialxe_input_add_info'] = $_SESSION['socialxe_input_add_info'];
			}
			
			unset($_SESSION['socialxe_input_add_info']);
			
			// �α� ���
			$info = new stdClass;
			$info->msg = $msg;
			$info->sns = $saved['service'];
			getModel('socialxe')->logRecord($this->act, $info);
			
			if($msg)
			{
				$this->setMessage($msg);
			}
			
			if(!$this->getRedirectUrl())
			{
				$this->setRedirectUrl($redirect_url);
			}
		}
		
 		/**
		 *@brief SNS ����
		 **/
        function procSocialxeSnsClear()
		{
            if(!Context::get('is_logged'))
			{
				return new BaseObject(-1, 'msg_not_logged');
			}
			
			if(!$service = Context::get('service'))
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}
			
			if(!$oLibrary = $this->getLibrary($service))
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}
			
			if(!($sns_info = getModel('socialxe')->getMemberSns($service)) || !$sns_info->name)
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}
			
			if($this->config->sns_login == 'Y' && $this->config->default_signup != 'Y')
			{
				$sns_list = getModel('socialxe')->getMemberSns();
				
				if(!is_array($sns_list))
				{
					$sns_list = array($sns_list);
				}
				
				if(count($sns_list) < 2)
				{
					return new BaseObject(-1, 'msg_not_clear_sns_one');
				}
			}
			
			$args = new stdClass;
			$args->service = $service;
			$args->member_srl = Context::get('logged_info')->member_srl;
			
			$output = executeQuery('socialxe.deleteMemberSns', $args);
			if(!$output->toBool())
			{
				return $output;
			}
			
			// ��ū �ֱ�
			getModel('socialxe')->setAvailableAccessToken($oLibrary, $sns_info, false);
			
			// ��ū �ı�
			$oLibrary->revokeToken();
			
			// �α� ���
			$info = new stdClass;
			$info->sns = $service;
			getModel('socialxe')->logRecord($this->act, $info);
			
			$this->setMessage('msg_success_sns_register_clear');
			
			$this->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'dispSocialxeSnsManage'));
        }
		
 		/**
		 *@brief SNS ��������
		 **/
        function procSocialxeSnsLinkage()
		{
            if(!Context::get('is_logged'))
			{
				return new BaseObject(-1, 'msg_not_logged');
			}
			
			if(!$service = Context::get('service'))
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}
			
			if(!$oLibrary = $this->getLibrary($service))
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}
			
			if(!($sns_info = getModel('socialxe')->getMemberSns($service)) || !$sns_info->name)
			{
				return new BaseObject(-1, 'msg_not_linkage_sns_info');
			}
			
			// ��ū �ֱ�
			getModel('socialxe')->setAvailableAccessToken($oLibrary, $sns_info);
			
			// ���� üũ
			if(($check = $oLibrary->checkLinkage()) && $check instanceof Object && !$check->toBool() && $sns_info->linkage != 'Y')
			{
				return $check;
			}
			
			$args = new stdClass;
			$args->service = $service;
			$args->linkage = ($sns_info->linkage == 'Y') ? 'N' : 'Y';
			$args->member_srl = Context::get('logged_info')->member_srl;
			
			$output = executeQuery('socialxe.updateMemberSns', $args);
			if(!$output->toBool())
			{
				return $output;
			}
			
			// �α� ���
			$info = new stdClass;
			$info->sns = $service;
			$info->linkage = $args->linkage;
			getModel('socialxe')->logRecord($this->act, $info);
			
			$this->setMessage('msg_success_linkage_sns');
			
			$this->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'dispSocialxeSnsManage'));
        }
		
 		/**
		 *@brief Callback
		 **/
        function procSocialxeCallback()
		{
			// ���� üũ
			if(!($service = Context::get('service')) || !in_array($service, $this->config->sns_services))
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}
			
			// ���̺귯�� üũ
			if(!$oLibrary = $this->getLibrary($service))
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}
			
			// ���� ���� üũ
			if(!$_SESSION['socialxe_auth']['state'])
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}
			
			if(!$type = $_SESSION['socialxe_auth']['type'])
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}
			
			$_SESSION['socialxe_current']['mid'] = $_SESSION['socialxe_auth']['mid'];
			$redirect_url = $_SESSION['socialxe_auth']['redirect'];
			$redirect_url = $redirect_url ? Context::getRequestUri() . '?' . $redirect_url : Context::getRequestUri();
			
			// ����
			$output = $oLibrary->authenticate();
			if($output instanceof Object && !$output->toBool())
			{
				$error = $output->getMessage();
			}
			
			// ���� ���� ����
			unset($_SESSION['socialxe_auth']);
			
			// �ε�
			if(!$error)
			{
				$output = $oLibrary->loading();
				if($output instanceof Object && !$output->toBool())
				{
					$error = $output->getMessage();
					
					// ������ ��ū �ı� (�ѹ�)
					$oLibrary->revokeToken();
				}
			}
			
			// ��� ó��
			if(!$error)
			{
				if($type == 'register')
				{
					$msg = 'msg_success_sns_register';
					
					$output = $this->registerSns($oLibrary);
					if(!$output->toBool())
					{
						$error = $output->getMessage();
					}
				}
				else if($type == 'login')
				{
					$output = $this->LoginSns($oLibrary);
					if(!$output->toBool())
					{
						$error = $output->getMessage();
					}
					
					// �α��� �� ������ �̵� (ȸ�� ���� ����)
					$redirect_url = getModel('module')->getModuleConfig('member')->after_login_url ?: getNotEncodedUrl('', 'mid', $_SESSION['socialxe_current']['mid'], 'act', '');
				}
			}
			
			// ����
			if($error)
			{
				$msg = $error;
				$this->setError(-1);
				
				if($type == 'login')
				{
					$redirect_url = getNotEncodedUrl('', 'mid', $_SESSION['socialxe_current']['mid'], 'act', 'dispMemberLoginForm');
				}
			}
			
			// �α� ���
			$info = new stdClass;
			$info->msg = $msg;
			$info->type = $type;
			$info->sns = $service;
			getModel('socialxe')->logRecord($this->act, $info);
			
			if($msg)
			{
				$this->setMessage($msg);
			}
			
			if(!$this->getRedirectUrl())
			{
				$this->setRedirectUrl($redirect_url);
			}
        }
		
 		/**
		 *@brief module Handler Ʈ����
		 **/
        function triggerModuleHandler(&$obj)
		{
			// SNS �α��� ���� �߰�
			if(Context::get('is_logged') && $_SESSION['sns_login'])
			{
				$logged_info = Context::get('logged_info');
				$logged_info->sns_login = $_SESSION['sns_login'];
				Context::set('logged_info', $logged_info);
			}
			
			if($this->config->default_signup != 'Y' && $this->config->sns_login == 'Y' && (Context::get('act') != 'dispMemberLoginForm' || Context::get('mode') == 'default'))
			{
				if(Context::get('module') == 'admin')
				{
					Context::addHtmlHeader('<style>.signin .login-footer, #access .login-body, #access .login-footer{display:none;}</style>');
				}
				else
				{
					Context::addHtmlHeader('<style>.signin .login-footer, #access .login-footer{display:none;}</style>');
				}
			}
			
			if(!Context::get('is_logged'))
			{
				return new BaseObject();
			}
			
			getController('member')->addMemberMenu('dispSocialxeSnsManage', 'sns_manage');			
			
			if(!in_array(Context::get('act'), array('dispMemberModifyInfo', 'dispMemberModifyEmailAddress')))
			{
				return new BaseObject();
			}
			
			if(getModel('socialxe')->memberUserSns())
			{
				if(Context::get('act') == 'dispMemberModifyInfo' || Context::get('act') == 'dispMemberModifyEmailAddress')
				{
					$_SESSION['rechecked_password_step'] = 'VALIDATE_PASSWORD';
				}
			}
			
            return new BaseObject();
        }
		
 		/**
		 *@brief module Object Before Ʈ����
		 **/
        function triggerModuleObjectBefore(&$obj)
		{
			if($this->config->sns_login != 'Y')
			{
				return new BaseObject();
			}
			
			$member_act = array('dispMemberSignUpForm', 'dispMemberFindAccount', 'procMemberInsert', 'procMemberFindAccount', 'procMemberFindAccountByQuestion');
			
			if($this->config->default_signup != 'Y' && in_array($obj->act, $member_act))
			{
				return new BaseObject(-1, 'msg_use_sns_login');
			}
			
			if($this->config->default_login != 'Y' && $obj->act == 'procMemberLogin')
			{
				return new BaseObject(-1, 'msg_use_sns_login');
			}
			
			if(!Context::get('is_logged'))
			{
				return new BaseObject();
			}
			
			if(!in_array($obj->act, array('dispMemberModifyPassword', 'procMemberModifyPassword', 'procMemberLeave', 'dispMemberLeave')))
			{
				return new BaseObject();
			}
			
			if(getModel('socialxe')->memberUserSns())
			{
				if((($obj->act == 'dispMemberModifyPassword' || $obj->act == 'procMemberModifyPassword') && (!getModel('socialxe')->memberUserPrev() || $this->config->default_login != 'Y')) || ($this->config->delete_member_forbid == 'Y' && ($obj->act == 'procMemberLeave' || $obj->act == 'dispMemberLeave')))
				{
					if($obj->act == 'dispMemberModifyPassword')
					{
						$obj->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', ''));
					}
					else
					{
						return new BaseObject(-1, 'msg_invalid_request');
					}
				}
				else if($obj->act == 'procMemberLeave')
				{
					$output = getController('member')->deleteMember(Context::get('logged_info')->member_srl);
					if(!$output->toBool())
					{
						return $output;
					}
					
					getController('member')->destroySessionInfo();
					
					$obj->setMessage('success_delete_member_info');
					
					$obj->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', ''));
				}
			}
			
            return new BaseObject();
        }
		
 		/**
		 *@brief module Object After Ʈ����
		 **/
        function triggerModuleObjectAfter(&$obj)
		{
			if($this->config->sns_login != 'Y')
			{
				return new BaseObject();
			}
			
			if(Mobile::isFromMobilePhone())
			{
				$template_path = sprintf('%sm.skins/%s/', $this->module_path, $this->config->mskin);
			}
			else
			{
				$template_path = sprintf('%sskins/%s/', $this->module_path, $this->config->skin);
			}
			
			// �α��� ������
			if($obj->act == 'dispMemberLoginForm' && (Context::get('mode') != 'default' || $this->config->default_login != 'Y'))
			{
				if(Context::get('is_logged'))
				{
					$obj->setRedirectUrl(getNotEncodedUrl('act', ''));
					
					return new BaseObject();
				}
				
				Context::set('config', $this->config);
				
				$obj->setTemplatePath($template_path);
				$obj->setTemplateFile('sns_login');
				
				foreach($this->config->sns_services as $key => $val)
				{
					$args = new stdClass;
					$args->auth_url = getModel('socialxe')->snsAuthUrl($val, 'login');
					$args->service = $val;
					$sns_services[$key] = $args;
				}
				
				Context::set('sns_services', $sns_services);
			}
			// ���� ���� ��߼�
			else if($obj->act == 'procMemberResetAuthMail')
			{
				$obj->setRedirectUrl(getNotEncodedUrl('', 'act', 'dispMemberLoginForm'));
			}
			
			if(!Context::get('is_logged'))
			{
				return new BaseObject();
			}
			
			if(!in_array($obj->act, array('dispMemberAdminInsert', 'dispMemberModifyInfo', 'dispMemberLeave')))
			{
				return new BaseObject();
			}
			
			if(getModel('socialxe')->memberUserSns())
			{
				if($obj->act == 'dispMemberLeave')
				{
					$obj->setTemplatePath($template_path);
					$obj->setTemplateFile('delete_member');
				}
				// ��й�ȣ ���� ����
				else if($obj->act == 'dispMemberModifyInfo')
				{
					$new_formTags = array();
					
					foreach(Context::get('formTags') as $no => $formInfo)
					{
						if($formInfo->name == 'find_account_question')
						{
							continue;
						}
						
						$new_formTags[] = $formInfo;
					}
					
					Context::set('formTags', $new_formTags);
				}
			}
			
			// ������ ȸ������ ���� SNS �׸� ����
			if($obj->act == 'dispMemberAdminInsert' && $member_srl = Context::get('member_srl'))
			{
				if(getModel('socialxe')->memberUserSns($member_srl))
				{
					$snsTag = array();
					
					foreach($this->config->sns_services as $key => $val)
					{
						if(!($sns_info = getModel('socialxe')->getMemberSns($val, $member_srl)) || !$sns_info->name)
						{
							continue;
						}
						
						$snsTag[] = sprintf('[%s] <a href="%s" target="_blank">%s</a>', ucwords($val), $sns_info->profile_url, $sns_info->name);
					}
					
					$snsTag = implode(', ', $snsTag);
					
					$new_formTags = array();
					
					foreach(Context::get('formTags') as $no => $formInfo)
					{
						if($formInfo->name == 'find_account_question')
						{
							$formInfo->name = 'sns_info';
							$formInfo->title = 'SNS';
							$formInfo->type = '';
							$formInfo->inputTag = $snsTag;
						}
						
						$new_formTags[] = $formInfo;
					}
					
					Context::set('formTags', $new_formTags);
				}
			}
			
            return new BaseObject();
        }
		
        /**
         * @brief display Ʈ����
         **/
        function triggerDisplay(&$output)
		{
			if($this->config->sns_login != 'Y')
			{
				return new BaseObject();
			}
			
			if(!Context::get('is_logged'))
			{
				return new BaseObject();
			}
			
			if(!in_array(Context::get('act'), array('dispMemberInfo', 'dispMemberModifyInfo', 'dispMemberAdminInsert')))
			{
				return new BaseObject();
			}
			
			if(getModel('socialxe')->memberUserSns())
			{
				if(Context::get('act') == 'dispMemberInfo')
				{
					if(!getModel('socialxe')->memberUserPrev() || $this->config->default_login != 'Y')
					{
						$output = preg_replace('/\<a[^\>]*?dispMemberModifyPassword[^\>]*?\>[^\<]*?\<\/a\>/is', '', $output);
					}
					
					if($this->config->delete_member_forbid == 'Y')
					{
						$output = preg_replace('/(\<a[^\>]*?dispMemberLeave[^\>]*?\>)[^\<]*?(\<\/a\>)/is', '', $output);
					}
					else
					{
						$output = preg_replace('/(\<a[^\>]*?dispMemberLeave[^\>]*?\>)[^\<]*?(\<\/a\>)/is', sprintf('$1%s$2', Context::getLang('delete_member_info')), $output);
					}
				}
				// ��й�ȣ ���� ����
				else if(Context::get('act') == 'dispMemberModifyInfo')
				{
					$output = preg_replace('/(\<input[^\>]*?value\=\"procMemberModifyInfo\"[^\>]*?\>)/is', sprintf('$1<input type="hidden" name="find_account_question" value="1" /><input type="hidden" name="find_account_answer" value="%s" />', cut_str(md5(date('YmdHis')), 13, '')), $output);
				}
			}
			
			// ������ ȸ������ ����
			if(Context::get('act') == 'dispMemberAdminInsert' && Context::get('member_srl'))
			{
				if(getModel('socialxe')->memberUserSns(Context::get('member_srl')))
				{
					$output = preg_replace('/(\<input[^\>]*?value\=\"procMemberAdminInsert\"[^\>]*?\>)/is', sprintf('$1<input type="hidden" name="find_account_question" value="1" /><input type="hidden" name="find_account_answer" value="%s" />', cut_str(md5(date('YmdHis')), 13, '')), $output);
				}
			}
			
			return new BaseObject();
		}
		
 		/**
		 *@brief ������� Ʈ����
		 **/
        function triggerInsertDocumentAfter($obj) 
		{
			if(!Context::get('is_logged'))
			{
				return new BaseObject();
			}
			
			// ������ ��� ����
			if($this->config->linkage_module_srl)
			{
				$module_srl_list = explode(',', $this->config->linkage_module_srl);
				
				if($this->config->linkage_module_target == 'exclude' && in_array($obj->module_srl, $module_srl_list) || $this->config->linkage_module_target != 'exclude' && !in_array($obj->module_srl, $module_srl_list))
				{
					return new BaseObject();
				}
			}
			
			if(!getModel('socialxe')->memberUserSns())
			{
				return new BaseObject();
			}
			
			foreach($this->config->sns_services as $key => $val)
			{
				if(!($sns_info = getModel('socialxe')->getMemberSns($val)) || $sns_info->linkage != 'Y')
				{
					continue;
				}
				
				if(!$oLibrary = $this->getLibrary($val))
				{
					continue;
				}
				
				// ��ū �ֱ�
				getModel('socialxe')->setAvailableAccessToken($oLibrary, $sns_info);
				
				$args = new stdClass;
				$args->title = $obj->title;
				$args->content = $obj->content;
				$args->url = getNotEncodedFullUrl('', 'document_srl', $obj->document_srl);
				$oLibrary->post($args);
				
				// �α� ���
				$info = new stdClass;
				$info->sns = $val;
				$info->title = $obj->title;
				getModel('socialxe')->logRecord('linkage', $info);
			}
			
			return new BaseObject();
		}
		
 		/**
		 *@brief ȸ����� Ʈ����
		 **/
        function triggerInsertMember(&$config) 
		{
			// �̸��� �ּ� Ȯ��
			if(Context::get('act') == 'procSocialxeConfirmMail')
			{
				$config->enable_confirm = 'Y';
			}
			// SNS �α��νÿ��� ���������� ������
			else if(Context::get('act') == 'procSocialxeCallback' || Context::get('act') == 'procSocialxeInputAddInfo')
			{
				$config->enable_confirm = 'N';
			}
			
			return new BaseObject();
		}
		
 		/**
		 *@brief ȸ���޴� �˾� Ʈ����
		 **/
		function triggerMemberMenu()
		{
			if(!($member_srl = Context::get('target_srl')) || $this->config->sns_profile != 'Y')
			{
				return new BaseObject();
			}
			
			if(!getModel('socialxe')->memberUserSns($member_srl))
			{
				return new BaseObject();
			}
			
			getController('member')->addMemberPopupMenu(getUrl('', 'mid', Context::get('cur_mid'), 'act', 'dispSocialxeSnsProfile', 'member_srl', $member_srl), 'sns_profile', '');
			
			return new BaseObject();
		}
		
 		/**
		 *@brief ȸ������ Ʈ����
		 **/
        function triggerDeleteMember($obj) 
		{
			$args = new stdClass;
			$args->member_srl = $obj->member_srl;
            $output = executeQueryArray('socialxe.getMemberSns', $args);
			
			$sns_id = array();
			
			foreach($output->data as $key => $val)
			{
				$sns_id[] = '['. $val->service . '] '. $val->id;
				
				if(!$oLibrary = $this->getLibrary($val->service))
				{
					continue;
				}
				
				// ��ū �ֱ�
				getModel('socialxe')->setAvailableAccessToken($oLibrary, $val, false);
				
				// ��ū �ı�
				$oLibrary->revokeToken();
			}
			
			executeQuery('socialxe.deleteMemberSns', $args);
			
			// �α� ���
			$info = new stdClass;
			$info->sns_id = implode(' | ', $sns_id);
			$info->nick_name = Context::get('logged_info')->nick_name;
			$info->member_srl = $obj->member_srl;
			getModel('socialxe')->logRecord('delete_member', $info);
			
			return new BaseObject();
		}
		
 		/**
		 *@brief SNS ���
		 **/
        function registerSns($oLibrary, $member_srl = null, $login = false)
		{
			if(!$member_srl)
			{
				$member_srl = Context::get('logged_info')->member_srl;
			}
			
			if($this->config->sns_login != 'Y' && !$member_srl)
			{
				return new BaseObject(-1, 'msg_not_sns_login');
			}
			
			if(!$oLibrary->getId())
			{
				return new BaseObject(-1, 'msg_errer_api_connect');
			}
			
			// SNS ���� ���� ���� üũ
			if(!$oLibrary->getVerified())
			{
				return new BaseObject(-1, 'msg_not_sns_verified');
			}
			
			$id = $oLibrary->getId();
			$service = $oLibrary->getService();
			
			$oSocialxeModel = getModel('socialxe');
			
			// SNS ID ��ȸ
			if(($sns_info = $oSocialxeModel->getMemberSnsById($id, $service)) && $sns_info->member_srl)
			{
				return new BaseObject(-1, 'msg_already_registed_sns');
			}
			
			$oMemberModel = getModel('member');
			
			// �ߺ� �̸��� ������ ������ �ش� �������� �α���
			if(!$member_srl && ($email = $oLibrary->getEmail()) && !$_SESSION['socialxe_confirm_email'])
			{
				if($member_srl = $oMemberModel->getMemberSrlByEmailAddress($email))
				{
					// ������ ������ ��� ���� ������ �ڵ����� ������� ����
					if($oMemberModel->getMemberInfoByMemberSrl($member_srl)->is_admin == 'Y')
					{
						return new BaseObject(-1, 'msg_request_admin_sns_login');
					}
					// �Ϲ� �����̶�� SNS ��� �� ��� �α��� ��û
					else
					{
						$do_login = true;
					}
				}
			}
			
			// ȸ�� ���� ����
			if(!$member_srl)
			{
				$password = cut_str(md5(date('YmdHis')), 13, '');
				$nick_name = preg_replace('/[\pZ\pC]+/u', '', $oLibrary->getName());
				
				if($oMemberModel->getMemberSrlByNickName($nick_name))
				{
					$nick_name = $nick_name . date('is');
				}
				
				// �߰� ���� ����
				if($this->config->sns_input_add_info[0] && !$_SESSION['socialxe_input_add_info_data'])
				{
					$_SESSION['tmp_socialxe_input_add_info'] = $oLibrary->get();
					$_SESSION['tmp_socialxe_input_add_info']['nick_name'] = $nick_name;
					
					return $this->setRedirectUrl(getNotEncodedUrl('', 'act', 'dispSocialxeInputAddInfo'), new BaseObject(-1, 'sns_input_add_info'));
				}
				
				// ���� �ּҸ� ������ �� ���ٸ� ���� �Է�
				if(!$email)
				{
					$_SESSION['tmp_socialxe_confirm_email'] = $oLibrary->get();
					
					return $this->setRedirectUrl(getNotEncodedUrl('', 'act', 'dispSocialxeConfirmMail'), new BaseObject(-1,'need_confirm_email_address'));
				}
				// ������ ���ٸ� ȸ�� ���� ����
				else
				{
					Context::setRequestMethod('POST');
					Context::set('password', $password, true);
					Context::set('nick_name', $nick_name, true);
					Context::set('user_name', $oLibrary->getName(), true);
					Context::set('email_address', $email, true);
					Context::set('accept_agreement', 'Y', true);
					
					$extend = $oLibrary->getProfileExtend();
					Context::set('homepage', $extend->homepage, true);
					Context::set('blog', $extend->blog, true);
					Context::set('birthday', $extend->birthday, true);
					Context::set('gender', $extend->gender, true);
					Context::set('age', $extend->age, true);
					
					// ����� �߰� ���� ����
					if($add_data = $_SESSION['socialxe_input_add_info_data'])
					{
						foreach($add_data as $key => $val)
						{
							Context::set($key, $val, true);
						}
					}
					
					unset($_SESSION['socialxe_input_add_info_data']);
				}
				
				// ȸ�� ��⿡ ���� ��û
				$output = getController('member')->procMemberInsert();
				
				// ���� ���� ������ �ִٸ� ��� ���
				if(is_object($output) && method_exists($output, 'toBool') && !$output->toBool())
				{
					if($output->error != -1)
					{
						$s_output = $output;
					}
					else
					{
						return $output;
					}
				}
				
				// ���� �Ϸ� üũ
				if(!$member_srl = $oMemberModel->getMemberSrlByEmailAddress($email))
				{
					return new BaseObject(-1, 'msg_error_register_sns');
				}
				
				// ���� �α��� ����� ������ ���� ����Ʈ ����
				if($oSocialxeModel->getSnsUser($id, $service))
				{
					Context::set('__point_message__', Context::getLang('PHC_member_register_sns_login'));
					
					getController('point')->setPoint($member_srl, 0, 'update');
				}
				
				// ���� ���
				if($extend->signature)
				{
					getController('member')->putSignature($member_srl, $extend->signature);
				}
				
				// ������ �̹��� ���
				if($oLibrary->getProfileImage())
				{
					if(($tmp_dir = 'files/cache/tmp/') && !is_dir($tmp_dir))
					{
						FileHandler::makeDir($tmp_dir);
					}
					
					$path_parts = pathinfo(parse_url($oLibrary->getProfileImage(), PHP_URL_PATH));
					$tmp_file = sprintf('%s%s.%s', $tmp_dir, $password, $path_parts['extension']);
					
					if(FileHandler::getRemoteFile($oLibrary->getProfileImage(), $tmp_file, null, 3, 'GET', null, array(), array(), array(), array('ssl_verify_peer' => false)))
					{
						getController('member')->insertProfileImage($member_srl, $tmp_file);
						
						@unlink($tmp_file);
					}
				}
			}
			// �̹� ���ԵǾ� �־��ٸ� SNS ��ϸ� ����
			else
			{
				// ����Ϸ��� ���񽺰� �̹� ��ϵǾ� ���� ���
				if(($sns_info = $oSocialxeModel->getMemberSns($service, $member_srl)) && $sns_info->member_srl)
				{
					// �α��ο��� ��� ��û�� �� ��� SNS ���� ���� �� ���� (SNS ID�� �޶����ٰ� �Ǵ�)
					if($login)
					{
						$args = new stdClass;
						$args->service = $service;
						$args->member_srl = $member_srl;
						executeQuery('socialxe.deleteMemberSns', $args);
					}
					else
					{
						return new BaseObject(-1, 'msg_invalid_request');
					}
				}
			}
			
			$args = new stdClass;
			$args->refresh_token = $oLibrary->getRefreshToken();
			$args->access_token = $oLibrary->getAccessToken();
			$args->profile_info = serialize($oLibrary->getProfile());
			$args->profile_url = $oLibrary->getProfileUrl();
			$args->profile_image = $oLibrary->getProfileImage();
			$args->email = $oLibrary->getEmail();
			$args->name = $oLibrary->getName();
			$args->id = $oLibrary->getId();
			$args->service = $service;
			$args->member_srl = $member_srl;
			
			// SNS ȸ�� ���� ���
			$output = executeQuery('socialxe.insertMemberSns', $args);
			if(!$output->toBool())
			{
				return $output;
			}
			
			// SNS ID ��� (SNS ������ ���� �Ǵ��� ID�� ���� ����)
			if(!$oSocialxeModel->getSnsUser($id, $service))
			{
				$output = executeQuery('socialxe.insertSnsUser', $args);
				if(!$output->toBool())
				{
					return $output;
				}
			}
			
			// �α��� ��û
			if($do_login)
			{
				$output = $this->LoginSns($oLibrary);
				if(!$output->toBool())
				{
					return $output;
				}
			}
			
			// ���� �Ϸ� �� �޼��� ��� (���� ���� �޼���)
			if($s_output)
			{
				return $s_output;
			}
			
			return new BaseObject();
        }
		
 		/**
		 *@brief SNS �α���
		 **/
        function LoginSns($oLibrary)
		{
			if($this->config->sns_login != 'Y')
			{
				return new BaseObject(-1, 'msg_not_sns_login');
			}
			
            if(Context::get('is_logged'))
			{
				return new BaseObject(-1, 'already_logged');
			}
			
			if(!$oLibrary->getId())
			{
				return new BaseObject(-1, 'msg_errer_api_connect');
			}
			
			// SNS ���� ���� ���� üũ
			if(!$oLibrary->getVerified())
			{
				return new BaseObject(-1, 'msg_not_sns_verified');
			}
			
			// SNS ID�� ȸ�� �˻�
			if(($sns_info = getModel('socialxe')->getMemberSnsById($oLibrary->getId(), $oLibrary->getService())) && $sns_info->member_srl)
			{
				// Ż���� ȸ���̸� ������ ��� �õ�
				if(!($member_info = getModel('member')->getMemberInfoByMemberSrl($sns_info->member_srl)) || !$member_info->member_srl)
				{
					$args = new stdClass;
					$args->member_srl = $sns_info->member_srl;
					executeQuery('socialxe.deleteMemberSns', $args);
				}
				// �α��� ���
				else
				{
					$do_login = true;
				}
			}
			
			// �˻��� ȸ������ �α��� ����
			if($do_login)
			{
				// ���� ����
				if($member_info->denied == 'Y')
				{
					$args = new stdClass;
					$args->member_srl = $member_info->member_srl;
					$output = executeQuery('member.chkAuthMail', $args);
					
					if($output->toBool() && $output->data->count > 0)
					{
						$_SESSION['auth_member_srl'] = $member_info->member_srl;
						
						return $this->setRedirectUrl(getNotEncodedUrl('', 'act', 'dispMemberResendAuthMail'), new BaseObject(-1,'msg_user_not_confirmed'));
					}
				}
				
				// ���� ���̵� ����
				if(getModel('member')->getMemberConfig()->identifier == 'email_address')
				{
					$user_id = $member_info->email_address;
				}
				else
				{
					$user_id = $member_info->user_id;
				}
				
				// ȸ�� ��⿡ �α��� ��û
				$output = getController('member')->doLogin($user_id, '', $this->config->sns_keep_signed == 'Y' ? true : false);
				if(!$output->toBool())
				{
					return $output;
				}
				
				// SNS ���� ���
				$_SESSION['sns_login'] = $oLibrary->getService();
				
				$args = new stdClass;
				$args->refresh_token = $oLibrary->getRefreshToken();
				$args->access_token = $oLibrary->getAccessToken();
				$args->profile_info = serialize($oLibrary->getProfile());
				$args->profile_url = $oLibrary->getProfileUrl();
				$args->profile_image = $oLibrary->getProfileImage();
				$args->email = $oLibrary->getEmail();
				$args->name = $oLibrary->getName();
				$args->service = $oLibrary->getService();
				$args->member_srl = $member_info->member_srl;
				
				// �α��νø��� SNS ȸ�� ���� ����
				$output = executeQuery('socialxe.updateMemberSns', $args);
				if(!$output->toBool())
				{
					return $output;
				}
			}
			// �˻��� ȸ���� ���� ��� SNS ���(����) ��û
			else
			{
				$output = $this->registerSns($oLibrary, null, true);
				if(!$output->toBool())
				{
					return $output;
				}
			}
			
			return new BaseObject();
        }
	}