<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class ghostexporter_Skin extends Skin
{
    /**
     * Get default name for the skin.
     * Note: the admin can customize it.
     */
    function get_default_name()
    {
        return 'Ghost Exporter';
    }


    /**
     * Get default type for the skin.
     */
    function get_default_type()
    {
    	return 'feed';
    }
}

?>
