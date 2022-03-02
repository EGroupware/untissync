<?php
/**
 * EGroupware - UntisSync - diverse hooks and admin tools
 *
 * @link http://www.egroupware.org
 * @author Axel Wild <info-AT-wild-solutions.de>
 * @package untissync
 * @copyright (c) 2020 by Axel Wild <info-AT-wild-solutions.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id$
 */

use EGroupware\Api\Framework;
use EGroupware\Api\Egw;
use EGroupware\Api\Acl;

if (!defined('UNTISSYNC_APP'))
{
	define('UNTISSYNC_APP','untissync');
}

/**
 * diverse hooks as static methods
 *
 */
class untissync_hooks
{

    /**
     * Hook called by link-class to include untissync in the appregistry of the linkage
     *
     * @param $args
     * @return string[]
     */
	function search_link($args)
	{
		return array(
			'add_popup'  => '800x600',
		);
	}

	/**
	 * hooks to build untissync's sidebox-menu plus the admin and Api\Preferences sections
	 *
	 * @param string/array $args hook args
	 */
	static function all_hooks($args)
	{
		$appname = UNTISSYNC_APP;
		$location = is_array($args) ? $args['location'] : $args;

		$config = untissync_config::read();
		$appmode = $config['app_mode'];

		if ($location == 'sidebox_menu')
		{
		    
		    display_sidebox($appname, lang('Favorites'), Framework\Favorites::list_favorites($appname));

			$title = $GLOBALS['egw_info']['apps']['untissync']['title'].' '.lang('Menu');

			$file = Array();
			
			$file[] = array(
    			'text' => 'substitutions',
    			'app'  => 'untissync',
    			'link' =>  Egw::link('/index.php',array(
    			    'menuaction' => 'untissync.untissync_ui.list',
    			    'ajax' => 'true',
    			))
			);
			
			$file[] = array(
			    'text' => 'info',
			    'app'  => 'untissync',
			    'link' =>  Egw::link('/index.php',array(
			        'menuaction' => 'untissync.untissync_ui.info',
			        'ajax' => 'true',
			    ))
			);

            if ($GLOBALS['egw_info']['user']['apps']['admin'])
            {
                $file[] = array(
                    'text' => 'admin-tools',
                    'app'  => 'untissync',
                    'link' =>  Egw::link('/index.php',array(
                        'menuaction' => 'untissync.untissync_ui.index',
                        'ajax' => 'true',
                    ))
                );
            }


			display_sidebox($appname,$title,$file);		
		}
		
		if ($GLOBALS['egw_info']['user']['apps']['admin'])
		{
		    $title = 'linking';
		    $file = Array();		    
		    
		    $file[] = array(
		        'text' => 'teacher',
		        //'icon' => Api\Image::find('schulmanager', 'book'),
		        'app'  => 'untissync',
		        'link' =>  Egw::link('/index.php',array(
		            'menuaction' => 'untissync.untissync_mapping_ui.te_mapping',
		            'ajax' => 'true',
		        ))
		    );
		    $file[] = array(
		        'text' => 'classes',
		        'app'  => 'untissync',
		        'link' =>  Egw::link('/index.php',array(
		            'menuaction' => 'untissync.untissync_mapping_ui.kl_mapping',
		            'ajax' => 'true',
		        ))
		    );
		    $file[] = array(
		        'text' => 'rooms',
		        'app'  => 'untissync',
		        'link' =>  Egw::link('/index.php',array(
		            'menuaction' => 'untissync.untissync_mapping_ui.ro_mapping',
		            'ajax' => 'true',
		        ))
		    );

		    display_sidebox($appname,$title,$file);
		}

		if ($GLOBALS['egw_info']['user']['apps']['admin'])
		{
			$file = Array(
				'Site Configuration' => Egw::link('/index.php',
				    'menuaction=admin.admin_config.index&appname=' . $appname,
				    '&ajax=true'),
			);
			if ($location == 'admin')
			{
				display_section($appname,$file);
			}
			else
			{
				display_sidebox($appname,lang('Admin'),$file);
			}
		}
	}

	/**
	 * populates $GLOBALS['settings'] for the Api\Preferences
	 */
	static function settings()
	{
		$settings = array();
		return $settings;
	}

	/**
	 * ACL rights and labels used by Untissync, not used right now
	 * @param string|array string with location or array with parameters incl. "location", specially "owner" for selected acl owner
	 */
	public static function acl_rights($params)
	{
		unset($params);	// not used, but required by function signature

		return array(
			Acl::READ    => 'read',
			Acl::EDIT    => 'edit',
			Acl::DELETE  => 'delete',
		);
	}

	/**
	 * Hook to tell framework we use standard categories method
	 *
	 * @param string|array $data hook-data or location
	 * @return boolean
	 */
	public static function categories($data)
	{
		unset($data);	// not used, but required by function signature

		return true;
	}
}
