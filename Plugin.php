<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 提供多个可选择的Gravatar头像服务器，同时提供默认头像设置。提供根据邮箱获取QQ头像功能
 * 
 * @package Gravatar Server
 * @author LT21
 * @author oPluss
 * @version 1.1.0
 * @link http://lt21.me
 * @link http://www.opluss.top
 */
class GravatarServer_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Abstract_Comments')->gravatar = array('GravatarServer_Plugin', 'render');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
    	
        /** 服务器 **/
        $server = new Typecho_Widget_Helper_Form_Element_Radio( 'server',  array(
                'http://cn.gravatar.com'        =>  'Gravatar CN （ http://cn.gravatar.com ）',
                'http://0.gravatar.com'         =>  'Gravatar 0 （ http://0.gravatar.com ）',
                'http://1.gravatar.com'         =>  'Gravatar 1 （ http://1.gravatar.com ）',
                'http://2.gravatar.com'         =>  'Gravatar 2 （ http://2.gravatar.com ）',
                'http://3.gravatar.com'         =>  'Gravatar 3 （ http://3.gravatar.com ）',
                'https://secure.gravatar.com'   =>  'Gravatar Secure （ https://secure.gravatar.com ）',
                'https://cdn.v2ex.com/gravatar'   =>  '国内镜像 （ https://cdn.v2ex.com/gravatar ）'),
            'http://cn.gravatar.com', _t('选择服务器'), _t('替换Typecho使用的Gravatar头像服务器（ www.gravatar.com ）') );
        $form->addInput($server->multiMode());

        /** 默认头像 **/
        $default = new Typecho_Widget_Helper_Form_Element_Radio( 'default',  array(
                'mm'            =>  '<img src=https://cdn.v2ex.com/gravatar/avatar/926f6ea036f9236ae1ceec566c2760ea?s=32&r=G&forcedefault=1&d=mm height="32" width="32" /> 神秘人物',
                'blank'         =>  '<img src=https://cdn.v2ex.com/gravatar/avatar/926f6ea036f9236ae1ceec566c2760ea?s=32&r=G&forcedefault=1&d=blank height="32" width="32" /> 空白',
                ''				=>  '<img src=https://cdn.v2ex.com/gravatar/avatar/926f6ea036f9236ae1ceec566c2760ea?s=32&r=G&forcedefault=1&d= height="32" width="32" /> Gravatar 标志',
                'identicon'     =>  '<img src=https://cdn.v2ex.com/gravatar/avatar/926f6ea036f9236ae1ceec566c2760ea?s=32&r=G&forcedefault=1&d=identicon height="32" width="32" /> 抽象图形（自动生成）',
                'wavatar'       =>  '<img src=https://cdn.v2ex.com/gravatar/avatar/926f6ea036f9236ae1ceec566c2760ea?s=32&r=G&forcedefault=1&d=wavatar height="32" width="32" /> Wavatar（自动生成）',
                'monsterid'     =>  '<img src=https://cdn.v2ex.com/gravatar/avatar/926f6ea036f9236ae1ceec566c2760ea?s=32&r=G&forcedefault=1&d=monsterid height="32" width="32" /> 小怪物（自动生成）'),
            'mm', _t('选择默认头像'), _t('当评论者没有设置Gravatar头像时默认显示该头像') );
        $form->addInput($default->multiMode());
        //其他配置
    	$othercfg = new Typecho_Widget_Helper_Form_Element_Checkbox( 'othercfg', 
	    	array('ifcheckqq' => '是否检测QQ头像',
	    		  'ignore_isSecure' => '是否强制使用所选源',
	    		  'debug' => 'DEBUG模式'),
	    	array('ifcheckqq'),
	    		'其他配置' );
	    $form->addInput($othercfg->multiMode());
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function render($size, $rating, $default, $comments)
    {
        $default = Typecho_Widget::widget('Widget_Options')->plugin('GravatarServer')->default;
        $othercfg = Typecho_Widget::widget('Widget_Options')->plugin('GravatarServer')->othercfg;
        $url = self::gravatarUrl($comments->mail, $size, $rating, $default, $othercfg, $comments->request->isSecure());
        if(!empty($othercfg) && in_array('debug', $othercfg))
        echo '<!--' . in_array('ifcheckqq', $othercfg) . '-->';
        echo '<img class="avatar" src="' . $url . '" alt="' . $comments->author . '" width="' . $size . '" height="' . $size . '" />';
    }

    /**
     * 获取gravatar头像地址 
     * 
     * @param string $mail 
     * @param int $size 
     * @param string $rating 
     * @param string $default 
     * @param string $othercfg
     * @param bool $isSecure 
     * @return string
     */
    public static function gravatarUrl($mail, $size, $rating, $default, $othercfg, $isSecure = false)
    {
    	// 检测是否是数字类型的QQ邮箱
    	if (!empty($othercfg) && in_array('ifcheckqq', $othercfg))
    	{
	    	if (preg_match('/^\d{4,11}@qq\.com$/i', $mail))
	    	{
	    		$qq = substr($mail, 0, -7);
	    		$geturl = 'http://ptlogin2.qq.com/getface?&imgtype=1&uin='.$qq;
				$qquser = file_get_contents($geturl);
				$str1 = explode('&k=', $qquser);
				$str2 = explode('&s=', $str1[1]);
				$k = $str2[0];
				$qqimg = 'https://q1.qlogo.cn/g?b=qq&k='.$k.'&s=100';
	    		return $qqimg;
	    	}
    	}
    	
        $url = $isSecure && (empty($othercfg) || !in_array('ignore_isSecure', $othercfg)) ? 'https://secure.gravatar.com' : Typecho_Widget::widget('Widget_Options')->plugin('GravatarServer')->server;
        $url .= '/avatar/';

        if (!empty($mail)) {
            $url .= md5(strtolower(trim($mail)));
        }

        $url .= '?s=' . $size;
        $url .= '&amp;r=' . $rating;
        $url .= '&amp;d=' . $default;

        return $url;
    }
}
