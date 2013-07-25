#Facebook Connect Wrapper for Kohana
######Kohana version: >= 3.2
####Usage
#####Create factory of object
<code>
$fb = Fb::factory();
</code>
#####Execute sign in or sign up account in User Model
<code>
$fb->sign();
</code>

####or
#####Get login url for authentication with Facebook (without pop-up)
<code>$fb->getLoginUrl();</code>
