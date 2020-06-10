## Rhymix SocialXE Module 
이 모듈은 conory님의 SocialXE 2015 모듈 V2.2의 포크입니다.

자세한 기능 및 이전 업데이트 내역은 오리지널 자료인 [이 글](https://xetown.com/point_contents/2930)을 확인해주세요.

 
## 라이선스
이 모듈은 Beerware 라이선스로, 제약조건이 없습니다.

이 모듈을 사용함으로 인해 일어나는 모든 책임은 사용자에게 있습니다. 이 모듈을 사용하시는 분들을 위해 추후 업데이트는 가능하다면 지원할 예정입니다.

이 모듈은 conory님 포크 버전이며, 기술지원 및 문의에 제약이 있을수도 없을수도 있습니다.


### 모듈 적용시 유의사항 (2020-06-10 추가 by RyanMoon)
- 레이아웃의 제작 환경에 따라 모듈의 각 act를 대응하지 못하는 경우도 있으니, 각 act 발생 시 서브페이지 형태로 페이지를 로드하도록 고쳐주어야 합니다. 대표적인 소셜XE의 disp, act 값은 3개입니다.
`'dispSocialxeInputAddInfo', 'dispSocialxeConfirmMail', 'dispMemberResendAuthMail'`
- 인증메일 절차는 XE 시스템상 생략할 수가 없습니다. 반드시 회원 설정에서 메일 인증 사용 설정과 웹마스터 메일 주소를 설정해서 인증메일이 정상 발송되도록 세팅해주어야 합니다.
- 카카오톡 OAuth2 인증시 동의항목 설정에서 이메일은 '사용안함'으로 하는 것을 권장합니다. 특히 "사용자에게 값이 없을 시 카카오 계정정보입력을 요청하여 수집" 옵션을 사용하면 인증메일의 인증 절차를 생략하고 로그인은 가능하지만, 반대로 인증 메일의 링크 클릭 시 "잘못된 인증"이라고 뜨기 때문에 사용자 입장에서 혼란스러울 수 있습니다.
- 


## 업데이트 및 수정 내용
- 모듈 적용시 유의사항 추가
- PHP 7.2 대응 ([원본 글](https://xetown.com/point_contents/1196345))
- Google OAuth2 인증 수정 적용 ([원본 글](https://xetown.com/point_contents/1196345))
- 카카오 OAuth2 인증 적용 수정 ([원본 글](https://xetown.com/index.php?&mid=point_contents&search_target=title_content&search_keyword=%EC%86%8C%EC%85%9C&document_srl=1360627))
- Google OAuth2 로그인 최신화

