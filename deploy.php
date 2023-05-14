<?php

// Origin.
const PLUGINS_ORIGIN_PATH = "";
const THEMES_ORIGIN_PATH  = "";

// Destiny.
const PLUGINS_DESTINY_PATH = "";
const THEMES_DESTINY_PATH  = "";

// Git.
const DEPLOY_BRANCH = 'stable';

/**
 * Colors class
 */
final class Colors {
    const GREEN  = 'green';
    const RED    = 'red';
    const YELLOW = 'yellow';

    /**
     * @var array
     */
    private $foreground_colors = [];

    /**
     * @var array
     */
    private $background_colors = [];

    public function __construct() {
        // Set up shell colors
        $this->foreground_colors['black']        = '0;30';
        $this->foreground_colors['dark_gray']    = '1;30';
        $this->foreground_colors['blue']         = '0;34';
        $this->foreground_colors['light_blue']   = '1;34';
        $this->foreground_colors['green']        = '0;32';
        $this->foreground_colors['light_green']  = '1;32';
        $this->foreground_colors['cyan']         = '0;36';
        $this->foreground_colors['light_cyan']   = '1;36';
        $this->foreground_colors['red']          = '0;31';
        $this->foreground_colors['light_red']    = '1;31';
        $this->foreground_colors['purple']       = '0;35';
        $this->foreground_colors['light_purple'] = '1;35';
        $this->foreground_colors['brown']        = '0;33';
        $this->foreground_colors['yellow']       = '1;33';
        $this->foreground_colors['light_gray']   = '0;37';
        $this->foreground_colors['white']        = '1;37';

        $this->background_colors['black']      = '40';
        $this->background_colors['red']        = '41';
        $this->background_colors['green']      = '42';
        $this->background_colors['yellow']     = '43';
        $this->background_colors['blue']       = '44';
        $this->background_colors['magenta']    = '45';
        $this->background_colors['cyan']       = '46';
        $this->background_colors['light_gray'] = '47';
    }

    /**
     * @param string $output_message
     * @param $foreground_color
     * @param null $background_color
     */
    public function _e( string $output_message, $foreground_color = null, $background_color = null ) {
        echo $this->getColoredString( $output_message, $foreground_color, $background_color ) . PHP_EOL;
    }

    // Returns colored string
    /**
     * @param $string
     * @param $foreground_color
     * @param null $background_color
     * @return mixed
     */
    public function getColoredString( $string, $foreground_color = null, $background_color = null ) {
        $colored_string = "";

        // Check if given foreground color found
        if ( isset( $this->foreground_colors[$foreground_color] ) ) {
            $colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m";
        }
        // Check if given background color found
        if ( isset( $this->background_colors[$background_color] ) ) {
            $colored_string .= "\033[" . $this->background_colors[$background_color] . "m";
        }

        // Add string and end coloring
        $colored_string .= $string . "\033[0m";

        return $colored_string;
    }

    // Returns all foreground color names
    public function getForegroundColors() {
        return array_keys( $this->foreground_colors );
    }

    // Returns all background color names
    public function getBackgroundColors() {
        return array_keys( $this->background_colors );
    }
}

/**
 * Check if exist change in current branch
 *
 * @return bool
 */
function check_if_exists_change_in_current_branch(): bool {
    exec( "git diff --quiet", $output, $code );

    if( $code === 0 ) {
        return false;
    }

    return true;
}

/**
 * Change to stable branch for get last release of your code.
 *
 * @return void
 */
function change_to_deploy_branch(): void{
    $current_branch = exec( "git rev-parse --abbrev-ref HEAD" );
    if ( DEPLOY_BRANCH !== $current_branch ) {
        exec( "git checkout " . DEPLOY_BRANCH );
    }
}

/**
 * Make a recursive copy
 *
 * @param  string $src
 * @param  string $dst
 * @return bool
 */
function recursive_copy( string $src, string $dst ): bool{
    $dir = opendir( $src );
    @mkdir( $dst );
    while ( false !== ( $file = readdir( $dir ) ) ) {
        if (  ( '.' != $file ) && ( '..' != $file ) ) {
            if ( is_dir( $src . '/' . $file ) ) {
                recursive_copy( $src . '/' . $file, $dst . '/' . $file );
                continue;
            }

            copy( $src . '/' . $file, $dst . '/' . $file );
        }
    }
    closedir( $dir );

    if ( is_dir( $dst ) ) {
        return true;
    }

    return false;
}

/**
 * Delete directories recursively
 *
 * @param  string $src
 * @return void
 */
function recursive_rmdir( string $src ) {
    $dir = opendir( $src );
    while ( false !== ( $file = readdir( $dir ) ) ) {
        if (  ( '.' != $file ) && ( '..' != $file ) ) {
            $full = $src . '/' . $file;
            if ( is_dir( $full ) ) {
                recursive_rmdir( $full );
                continue;
            }

            unlink( $full );
        }
    }
    closedir( $dir );
    rmdir( $src );
}

/**
 * Deploy plugins/themes into destination repository
 *
 * @param  array   $directories
 * @param  boolean $plugins
 * @return void
 */
function deploy( array $directories, bool $plugins = false ) {
    $colors = new Colors();

    $dst_path = $plugins ? PLUGINS_DESTINY_PATH : THEMES_DESTINY_PATH;
    $src_path = $plugins ? PLUGINS_ORIGIN_PATH : THEMES_ORIGIN_PATH;

    foreach ( $directories as $directory ) {
        $path = $dst_path . $directory;
        if ( is_dir( $path ) ) {
            rename( $path, $path . '_old' );
            $colors->_e( "Plugin directory {$path} renamed.", $colors::GREEN );
        }

        $copy = recursive_copy(
            $src_path . $directory,
            $dst_path . $directory
        );

        if ( !$copy ) {
            $colors->_e( "Something was wrong copying plugin directory...", $colors::RED );
            continue;
        }

        $colors->_e( "Plugin directory {$path} copied succesfully!", $colors::GREEN );
        recursive_rmdir( $path . "_old" );
    }
}

// Colors class.
$colors = new Colors();

if( check_if_exists_change_in_current_branch() ) {
    $colors->_e( "There are changes in the current branch, resolves them and run this script again.", $colors::RED );
    die();
}

change_to_deploy_branch();

// Plugins to deploy.
$plugins = [
];

// Themes to deploy.
$themes = [
];

$colors->_e( "Deploying plugins into destination repository...", $colors::YELLOW );
deploy( $plugins, true );

$colors->_e( "Deploying themes into destination repository...", $colors::YELLOW );
deploy( $themes );
