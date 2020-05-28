<?php
	/**
	 * @class  libraryGoogle
     * @author CONORY (https://xe.conory.com)
	 * @brief The google library of the socialxe module
	 * 
	 */

	const GOOGLE_OAUTH2_URI 	= 'https://accounts.google.com/o/oauth2/';
	//const GOOGLE_PEOPLE_URI 	= 'https://www.googleapis.com/plus/v1/people/';
	const GOOGLE_PROFILE_URI	= 'https://www.googleapis.com/oauth2/v1/userinfo';

	class libraryGoogle extends socialxeLibrary
	{
		/**
		 * @brief 인증 URL 생성 (SNS 로그인 URL)
		 */
		function createAuthUrl($type)
		{
			// API 권한
			$scope = array(
				'openid',
				'https://www.googleapis.com/auth/userinfo.email',
				'https://www.googleapis.com/auth/userinfo.profile',
			);

			// 요청 파라미터
			$params = array(
				'scope' => implode(' ', $scope),
				'access_type' => 'offline',
				'response_type' => 'code',
				'client_id' => $this->config->google_client_id,
				'redirect_uri' => getNotEncodedFullUrl('', 'module', 'socialxe', 'act', 'procSocialxeCallback', 'service', 'google'),
				'state' => $_SESSION['socialxe_auth']['state'],
			);

			return GOOGLE_OAUTH2_URI . 'auth?' . http_build_query($params, '', '&');
		}

		/**
		 * @brief 인증 단계 (로그인 후 callback 처리) [실행 중단 에러를 출력할 수 있음]
		 */
		function authenticate()
		{
			// 위변조 체크
			if(!Context::get('code') || Context::get('state') !== $_SESSION['socialxe_auth']['state'])
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}

			// API 요청 : 엑세스 토큰
			$token = $this->requestAPI('token', array(
				'code' => Context::get('code'),
				'grant_type' => 'authorization_code',
				'client_id' => $this->config->google_client_id,
				'client_secret' => $this->config->google_client_secret,
				'redirect_uri' => getNotEncodedFullUrl('', 'module', 'socialxe', 'act', 'procSocialxeCallback', 'service', 'google'),
			));

			// 토큰 삽입
			$this->setIdToken($token['id_token']);
			$this->setAccessToken($token['access_token']);
			$this->setRefreshToken($token['refresh_token']);
			
			return new BaseObject();
		}

		/**
		 * @brief 로딩 단계 (인증 후 프로필 처리) [실행 중단 에러를 출력할 수 있음]
		 */
		function loading()
		{
			// 토큰 체크
			if(!$this->getAccessToken())
			{
				return new BaseObject(-1, 'msg_errer_api_connect');
			}

			$id_token 		= $this->getIdToken();
			$id_token_arr 	= explode(".", $id_token);

			$jwtHeaderStr 	= base64_decode($id_token_arr[0]);
			$jwtPayloadStr 	= base64_decode($id_token_arr[1]);
			$jwtSigStr		= base64_decode($id_token_arr[2]);

			$jwtHeader 		= json_decode($jwtHeaderStr, true);
			$jwtPayload 	= json_decode($jwtPayloadStr, true);
			$jwtSignature	= json_decode($jwtSigStr, true);
			
			// API 요청 : 프로필
			$profile = $this->requestAPI(GOOGLE_PROFILE_URI . "?" . http_build_query(array(
				'access_token' => $this->getAccessToken(),
				'alt' => "json",
			), '', '&'));

			if($jwtPayload["email"] != $profile["email"] || $profile["verified_email"] == false)
			{
				return new BaseObject(-1, 'msg_errer_api_connect');
			}
		
			// 프로필 데이터가 없다면 오류
			if(empty($profile))
			{
				return new BaseObject(-1, 'msg_errer_api_connect');
			}

			// 팔로워 수 제한
			if($this->config->sns_follower_count)
			{
				if($this->config->sns_follower_count > $profile['circledByCount'])
				{
					$this->revokeToken();

					return new BaseObject(-1, sprintf(Context::getLang('msg_not_sns_follower_count'), $this->config->sns_follower_count));
				}
			}

			// 이메일 주소, ID, 이름, 프로필 이미지, 프로필 URL
			$this->setEmail($profile['email']);
			$this->setId($profile['id']);
			$this->setName($profile['name']);
			$this->setProfileImage($profile['picture']);
			$this->setProfileUrl($profile['picture']);

			// 프로필 인증
			$this->setVerified(true);

			// 전체 데이터
			$this->setProfileEtc($profile);

			return new BaseObject();
		}

		/**
		 * @brief 토큰 파기 (SNS 해제 또는 회원 삭제시 실행)
		 */
		function revokeToken()
		{
			// 토큰 체크
			if(!($token = $this->getRefreshToken() ?: $this->getAccessToken()))
			{
				return;
			}

			// API 요청 : 토큰 파기
			$this->requestAPI('revoke', array(
				'token' => $token,
			));
		}

		/**
		 * @brief 토큰 새로고침 (로그인 지속이 되어 토큰 만료가 될 경우를 대비)
		 */
		function refreshToken()
		{
			// 토큰 체크
			if(!$this->getRefreshToken())
			{
				return;
			}

			// API 요청 : 토큰 새로고침
			$token = $this->requestAPI('token', array(
				'refresh_token' => $this->getRefreshToken(),
				'grant_type' => 'refresh_token',
				'client_id' => $this->config->google_client_id,
				'client_secret' => $this->config->google_client_secret,
			));

			// 새로고침 된 토큰 삽입
			$this->setAccessToken($token['access_token']);
		}

		/**
		 * @brief 프로필 확장 (가입시 추가 기입)
		 */
		function getProfileExtend()
		{
			// 프로필 체크
			if(!$profile = $this->getProfileEtc())
			{
				return new stdClass;
			}

			$extend = new stdClass;

			// 서명 (자기 소개)
			if($profile['aboutMe'] || $profile['tagline'])
			{
				$extend->signature = $profile['aboutMe'] ?: $profile['tagline'];
			}

			// 홈페이지
			if($profile['urls'])
			{
				foreach($profile['urls'] as $key => $val)
				{
					if($val['type'] == 'other' && $val['value'])
					{
						$extend->homepage = $val['value'];

						break;
					}
				}
			}

			// 생일
			if($profile['birthday'])
			{
				$extend->birthday = preg_replace('/[^0-9]*?/', '', $profile['birthday']);
			}

			// 성별
			if($profile['gender'] == 'male')
			{
				$extend->gender = '남성';
			}
			else if($profile['gender'] == 'female')
			{
				$extend->gender = '여성';
			}

			// 연령대
			if($profile['ageRange']['min'] || $profile['ageRange']['max'])
			{
				if($profile['ageRange']['min'] && $profile['ageRange']['max'])
				{
					$age = ($profile['ageRange']['min'] + $profile['ageRange']['max']) / 2;
				}
				else
				{
					$age = max($profile['ageRange']['min'], $profile['ageRange']['max']);
				}

				$extend->age = floor($age / 10) * 10 . '대';
			}

			return $extend;
		}

		function getProfileImage()
		{
			// 최대한 큰 사이즈의 프로필 이미지를 반환하기 위하여
			return preg_replace('/\?.*/', '', parent::getProfileImage());
		}

		function requestAPI($url, $post = array())
		{
			$data = FileHandler::getRemoteResource(
				in_array($url, array('token', 'revoke')) ? GOOGLE_OAUTH2_URI . $url : $url,
				null,
				3,
				empty($post) ? 'GET' : 'POST',
				'application/x-www-form-urlencoded',
				array(),
				array(),
				$post,
				array('ssl_verify_peer' => false)
			);
			//error_log($data); 	// for debug
			$res = json_decode($data, true);
			return $res;
		}
	}
