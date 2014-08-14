<?php
/*
Plugin Name: DW Mobile Switcher
Plugin URI: http://Devework.com/
Description: DeveWork旗下移动主题专用主题（如DeveMobile）切换插件。(更新：2014.8.14)
Version: 1.0
Author: Jeff
Author URI: http://Devework.com/
@ Thanks to mg12’s WP Mobile themes plugin.
*/

/**
* 实验性质：添加psot-meta标签 -Start
* @since 8.14
*/
function mobile_custom_meta_box() {     
    add_meta_box(     
        'mobile_custom_meta_box', // $id     
        '移动主题编辑相关（非必填）', //  显示meta_box标题   
        'mobile_show_custom_meta_box', // 命名回调函数    
        'post', //  选择发布类型   
        'normal', // $context     
        'high'); //  权限   
} 
add_action('add_meta_boxes', 'mobile_custom_meta_box');

$prefix = 'mobile_';   
  $custom_meta_fields = array(  //初始化数组 
    array(     
          'label'=> '外链特色图像',//标记label名
          'id'    => 'thumb',  //custom_text 为输入框标记唯一的id名  
          'desc'  => '输入图片的url，支持外链。即可优先显示（不填亦可）',//输入框描述 
          'type'  => 'text' //选择输入类型   
      ),
    /*
    array(     
          'label'=> '文章来源文字',//标记label名
          'id'    => 'f',  //custom_text 为输入框标记唯一的id名  
          'desc'  => '如果是转载文章，请输入来源的站点或网站名（需要主题设置开启显示来源方可有效）',//输入框描述 
          'type'  => 'text' //选择输入类型   
      ),
    array(     
          'label'=> '文章来源链接',//标记label名
          'id'    => 'furl',  //custom_text 为输入框标记唯一的id名  
          'desc'  => '如果是转载文章，请输入来源链接（需要主题设置开启显示来源方可有效）',//输入框描述 
          'type'  => 'text' //选择输入类型   
      ),*/
  );     

function mobile_show_custom_meta_box() {     
global $custom_meta_fields, $post;     
//   添加即时验证函数   
echo '<input type="hidden" name="custom_meta_box_nonce" value="'.wp_create_nonce(basename(__FILE__)).'" />';     
         
    //  开始循环出metabox 输入框   
    echo '<table class="form-table">';     
    foreach ($custom_meta_fields as $field) {  //对之前存储在变量$custom_meta_fields中的数组进行遍历   
            
        $meta = get_post_meta($post->ID, $field['id'], true);  //提取出每个字段的id   
        echo '<tr>    
                <th><label for="'.$field['id'].'">'.$field['label'].'</label></th>    
                <td>';     
                switch($field['type']) {  //遍历输入框类型   
                        // text    
    case 'text':     
        echo '<input type="text" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$meta.'" size="30" />    
            <br /><span class="description">'.$field['desc'].'</span>';     
    break;     
       
        // textarea     
    case 'textarea':     
        echo '<textarea name="'.$field['id'].'" id="'.$field['id'].'" cols="60" rows="4">'.$meta.'</textarea>    
            <br /><span class="description">'.$field['desc'].'</span>';     
    break;                                  
                }    
        echo '</td></tr>';     
    } // end foreach     
    echo '</table>'; // end table            
}   

function mobile_save_custom_meta_box($post_id) {     
      global $custom_meta_fields;     
           
  //验证刚才创建的即时验证函数      
      if (!wp_verify_nonce($_POST['custom_meta_box_nonce'], basename(__FILE__)))   
          return $post_id;     
      //   检查自动存储   
      if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)     
          return $post_id;     
      // 检查发布权限是否对应发布类型   
      if ('page' == $_POST['post_type']) {     
          if (!current_user_can('edit_page', $post_id))     
              return $post_id;     
          } elseif (!current_user_can('edit_post', $post_id)) {     
              return $post_id;     
      }     
           
      // 通过字段循环存储数据   
      foreach ($custom_meta_fields as $field) {     
          $old = get_post_meta($post_id, $field['id'], true);     
          $new = $_POST[$field['id']];     
          if ($new && $new != $old) {     
              update_post_meta($post_id, $field['id'], $new);  //更新数据   
          } elseif ('' == $new && $old) {     
              delete_post_meta($post_id, $field['id'], $old);  //如果为更新，则沿用之前字段中的数据   
          }     
      } // end foreach     
  }     
  add_action('save_post', 'mobile_save_custom_meta_box'); 


/**
*实验性质：添加psot-meta标签 -End
*/

//移动主题导航菜单
if(function_exists('register_nav_menu')){
    register_nav_menu( 'mobilemenu', '移动主题菜单' );
}


//cookie support (检测是否存在cookie，如果存在则不做主题切换等工作)
if (!isset($_COOKIE['return_desktop'])){

//载入user-agent检测插件Mobile_Detect
require_once 'Mobile_Detect.php';

//执行主题切换函数
class DWMobileSwitcher {
	private static $theme;
    private static $detect;
	function DWMobileSwitcher($mobileTheme, $tabletTheme) {
		$detect = new DW_Mobile_Detect();

		if($detect->isMobile()) {
			if($tabletTheme && $detect->isTablet()) {
				$this->theme = $tabletTheme;
			} else if($mobileTheme) {
				$this->theme = $mobileTheme;
			}
			if($this->theme){
				add_filter('stylesheet', array(&$this, 'getStylesheet'));
				add_filter('template', array(&$this, 'getTemplate'));
			}
		}
	}

	public function getTemplate() {
		$theme = $this->theme;
		if (empty($theme)) {
			return $template;
		}
		$theme = get_theme($theme);
		if (empty($theme)) {
			return $template;
		}
		// 不显示非公开主题模板
		if (isset($theme['Status']) && $theme['Status'] != 'publish') {
			return $template;
		}
		return $theme['Template'];
	}

	public function getStylesheet($theme) {
		$theme = $this->theme;
		if (empty($theme)) {
			return $stylesheet;
		}
		$theme = get_theme($theme);
		//不显示非公开主题模板
		if (isset($theme['Status']) && $theme['Status'] != 'publish') {
			return $template;
		}		
		if (empty($theme)) {
			return $stylesheet;
		}
		return $theme['Stylesheet'];
	}
}

// apply mobile theme激活手机主题
$options = get_option('dw_mobile_switcher_options');
$mobileThemeName = $options['mobile_theme'];
if(!$mobileThemeName) {
	$mobileThemeName = get_current_theme();
}
$tabletThemeName = $options['tablet_theme'];
if(!$tabletThemeName) {
	$tabletThemeName = get_current_theme();
}
new DWMobileSwitcher($mobileThemeName, $tabletThemeName);

}//cookie support end

// add settings link to plugin item
function actionLinks( $links ) {
	$settingsLink = '<a href="/wp-admin/themes.php?page=dw-mobile-switcher.php">' . __('设置') . '</a>'; 
	array_unshift($links, $settingsLink);
	return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'actionLinks');


/*设置界面*/
class DWMobileSwitcherOptions {

	/*get settings*/
	private function getOptions() {
		$options = get_option('dw_mobile_switcher_options');
		if(!is_array($options)) {
			$options['mobile_theme'] = '';
			$options['tablet_theme'] = '';
			update_option('dw_mobile_switcher_options', $options);
		}
		return $options;
	}

	/* update settings 更新设置 */
	public function updateOptions() {
		if(isset($_POST['dw_mobile_switcher_save'])) {
			$options = DWMobileSwitcherOptions::getOptions();
			$themeNames = DWMobileSwitcherOptions::getThemeNames();
			$options['mobile_theme'] = $_POST['mobile_theme'];
			$options['tablet_theme'] = $_POST['tablet_theme'];

			if(!DWMobileSwitcherOptions::isThemeIncluded($options['mobile_theme'], $themeNames)) {
				$options['mobile_theme'] = DWMobileSwitcherOptions::getDefaultThemeName();
			}
	
			if(!DWMobileSwitcherOptions::isThemeIncluded($options['tablet_theme'], $themeNames)) {
				$options['tablet_theme'] = DWMobileSwitcherOptions::getDefaultThemeName();
			}
	
			update_option('dw_mobile_switcher_options', $options);

		} else {
			DWMobileSwitcherOptions::getOptions();
		}
		// add settings page to menu
		add_menu_page('DW Mobile Switcher 设置页面', '移动主题', 'edit_theme_options', basename(__FILE__), array('DWMobileSwitcherOptions', 'display'),'dashicons-welcome-view-site');
	}

	/*display form 展示表格*/
	public function display() {
		$options = DWMobileSwitcherOptions::getOptions();
		$themeNames = DWMobileSwitcherOptions::getThemeNames();
		$mobileThemeName = $options['mobile_theme'];
		$tabletThemeName = $options['tablet_theme'];
?>

<div class="wrap">
	<h2>DW Mobile Switcher 设置页面</h2>

	<?php if(!empty($_POST)) : ?>
		<div class='updated fade'><p>设置保存成功！</p></div>
	<?php endif; ?>

	<div id="poststuff" class="has-right-sidebar">
		<div id="post-body">
			<div id="post-body-content">
				<form action="#" method="POST" name="wp_mobile_themes_form">
					<table class="form-table">
						<tbody>
				
									<p  style="font-size: 14px;"><?php printf('<div class="dashicons dashicons-desktop"></div>当前电脑端（PC桌面）主题：<a href="/wp-admin/themes.php">%1$s</a>。', DWMobileSwitcherOptions::getDefaultThemeName()); ?></p>
									<p  style="font-size: 14px;">使用手机和平板访问网站的用户将看到以下选择的移动主题界面：</p>									
				
							<tr valign="top">
								<th scope="row"><div class="dashicons dashicons-smartphone"></div>手机主题：</th>
								<td >
									<select name="mobile_theme">
										<?php
											foreach ($themeNames as $themeName) {
												$selectedProperty = '';
												$defaultTip = '';

												if($themeName == $mobileThemeName) {
													$selectedProperty = ' selected="selected"';
												}
												if($themeName == DWMobileSwitcherOptions::getDefaultThemeName()) {
													$defaultTip = __(' (deault)', 'dw-mobile-switcher');
												}
												echo '<option value="' . $themeName . '"' . $selectedProperty . '>' . htmlspecialchars($themeName) . $defaultTip . '</option>';
											}
										?>
									<select>
									<p class="description">手机主题将应用在 iPhone、iPod touch、Nexus、BlackBerry等手机和小型移动设备上。</p>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row"><div class="dashicons dashicons-tablet"></div>平板主题：</th>
								<td>
									<select name="tablet_theme">
										<?php
											foreach ($themeNames as $themeName) {
												$selectedProperty = '';
												$defaultTip = '';

												if($themeName == $tabletThemeName) {
													$selectedProperty = ' selected="selected"';
												}
												if($themeName == DWMobileSwitcherOptions::getDefaultThemeName()) {
													$defaultTip = __(' (deault)', 'dw-mobile-switcher');
												}
												echo '<option value="' . $themeName . '"' . $selectedProperty . '>' . htmlspecialchars($themeName) . $defaultTip . '</option>';
											}
										?>
									<select>
									<p class="description">平板主题将应用在 iPad、Kindle、Nexus 平板、三星平板等平板设备上。</p>
								</td>
							</tr>

						</tbody>
					</table>

					<p class="submit">
						<input class="button-primary" type="submit" name="dw_mobile_switcher_save" value="保存设置" />
					</p>
						<p>使用须知：本插件为DeveWork.com 旗下的移动主题（如DeveMobile 主题）专属主题切换插件，请确保您已经购买相关主题。</p>
						<p>使用说明：在“手机主题”“平板主题”选择相应的移动主题，保存即可。</p>
						<p>高级接口：本插件支持移动主题与电脑主题的手动切换，如果使用DeveWork.com 旗下的移动主题默认有<code>手机→电脑主题</code>的入口；但相对应的电脑主题上则需要添加代码以提供<code>电脑→手机主题</code>的接口：</p>												
						<p>
						<pre style="padding:10px;margin:15px 0;font:100 12px/18px;"Consolas" , "Courier New" ,monaco, andale mono, courier new;padding:10px 12px;border:#ccc 1px solid;border-left-width:4px;background-color:#fefefe;box-shadow:0 0 4px #eee;word-break:break-all;word-wrap:break-word;color:#444"><span style="color:#170">&lt;script</span> <span style="color:#00c">type</span>=<span style="color:#a11">"text/javascript"</span><span style="color:#170">&gt;</span><br><span style="color:#708">function</span> <span style="color:#000">ReturnMobile</span>(){<br>   <span style="color:#708">var</span> <span style="color:#00f">expires</span> = <span style="color:#708">new</span> <span style="color:#000">Date</span>();<br>   <span style="color:#000-2">expires</span>.<span style="color:#000">setTime</span>(<span style="color:#000-2">expires</span>.<span style="color:#000">getTime</span>()-<span style="color:#164">1</span>);<br>   <span style="color:#000">document</span>.<span style="color:#000">cookie</span> = <span style="color:#a11">'return_desktop=1;path=/;expires='</span> + <span style="color:#000-2">expires</span>.<span style="color:#000">toGMTString</span>();<br>}<br><span style="color:#170">&lt;/script</span><span style="color:#170">&gt;</span><br><span style="color:#555">&lt;?php</span> <span style="color:#708">if</span> ( <span style="color:#@cm-word">wp_is_mobile</span>() ) {<span style="color:#555">?&gt;</span><br>   <span style="color:#170">&lt;a</span> <span style="color:#00c">onclick</span>=<span style="color:#a11">"ReturnMobile()"</span> <span style="color:#00c">href</span>=<span style="color:#a11">"javascript:window.location.reload();"</span><span style="color:#170">&gt;</span>切换回移动版<span style="color:#170">&lt;/a</span><span style="color:#170">&gt;</span><br><span style="color:#555">&lt;?php</span> }<span style="color:#555">?&gt;</span></pre></p>
						<p>将上面的代码添加到当前pc主题的适当位置（一般为页脚处），酌情添加css样式即可。</p>
				</form>
			</div>
		</div>
	</div>
</div>

<?php
	}

	/*return the name of themes*/
	private function getThemeNames() {
		$themes = get_themes();
		$themeNames = array_keys($themes);
		natcasesort($themeNames);

		return $themeNames;
	}

	/*return the name of default theme*/
	private function getDefaultThemeName() {
		$themeName = get_current_theme();
		return $themeName;
	}

	/* is the theme included*/
	private function isThemeIncluded($obj, $list) {
		foreach ($list as $item) {
			if($item == $obj) {
				return true;
			}
		}

		return false;
	}
}

add_action('admin_menu', array('DWMobileSwitcherOptions', 'updateOptions'));



?>
