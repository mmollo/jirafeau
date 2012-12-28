<?php
/*
 *  Jirafeau, your web file repository
 *  Copyright (C) 2008  Julien "axolotl" BERNARD <axolotl@magieeternelle.org>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
define ('JIRAFEAU_ROOT', dirname (__FILE__) . '/');
define ('NL', "\n");
define ('QUOTE', "'");

define ('JIRAFEAU_CFG', JIRAFEAU_ROOT.'lib/config.local.php');
define ('JIRAFEAU_VAR_RAND_LENGTH', 15);

require (JIRAFEAU_ROOT . 'lib/lang.php');
require (JIRAFEAU_ROOT . 'lib/config.php');

function
jirafeau_quoted ($str)
{
    return QUOTE . str_replace (QUOTE, "\'", $str) . QUOTE;
}

function
jirafeau_export_cfg ($cfg)
{
    $handle = fopen (JIRAFEAU_CFG, 'w');
    fwrite ($handle, '<?php' . NL);
    fwrite ($handle,
            '/* ' .
            _ ('This file was generated by the install process. ' .
               'You can edit it. Please see config.php to understand the ' .
               'configuration items.') . ' */' . NL);
    foreach ($cfg as $key => $item)
    {
        fwrite ($handle, '$cfg[' . jirafeau_quoted ($key) . '] = ');
        if (is_bool ($item))
            fwrite ($handle, ($item ? 'true' : 'false'));
        else if (is_string ($item))
            fwrite ($handle, jirafeau_quoted ($item));
        else
            fwrite ($handle, 'null');
        fwrite ($handle, ';'.NL);
    }
    /* No newline at the end of the file to be able to send headers. */
    fwrite ($handle, '?>');
    fclose ($handle);
}

function
jirafeau_mkdir ($path)
{
    if (!file_exists ($path) &&  !@mkdir ($path, 0755))
        return false;
    return true;
}

/**
 * Returns true whether the path is writable or we manage to make it
 * so, which essentially is the same thing.
 * @param $path is the file or directory to be tested.
 * @return true if $path is writable.
 */
function
jirafeau_is_writable ($path)
{
    /* "@" gets rid of error messages. */
    return is_writable ($path) || @chmod ($path, 0777);
}

function
jirafeau_check_var_dir ($path)
{
    $mkdir_str1 = _('The following directory could not be created') . ':';
    $mkdir_str2 = _('You should create this directory by hand.');
    $write_str1 = _('The following directory is not writable') . ':';
    $write_str2 = _('You should give the write right to the web server on ' .
                    'this directory.');
    $solution_str = _('Here is a solution') . ':';

    if (!jirafeau_mkdir ($path) || !jirafeau_is_writable ($path))
        return array ('has_error' => true,
                      'why' => $mkdir_str1 . '<br /><code>' .
                               $path . '</code><br />' . $solution_str .
                               '<br />' . $mkdir_str2);

    foreach (array ('files', 'links') as $subdir)
    {
        $subpath = $path.$subdir;

        if (!jirafeau_mkdir ($subpath) || !jirafeau_is_writable ($subpath))
            return array ('has_error' => true,
                          'why' => $mkdir_str1 . '<br /><code>' .
                                   $subpath . '</code><br />' . $solution_str .
                                   '<br />' . $mkdir_str2);
    }

    return array ('has_error' => false, 'why' => '');
}

function
jirafeau_add_ending_slash ($path)
{
    return $path . ((substr ($path, -1) == '/') ? '' : '/');
}

if (!file_exists (JIRAFEAU_CFG))
{
    /* We try to create an empty one. */
    if (!@touch (JIRAFEAU_CFG))
    {
        require (JIRAFEAU_ROOT . 'lib/template/header.php');
        echo '<div class="error"><p>' .
             _('The local configuration file could not be created. Create a ' .
               '<code>lib/config.local.php</code> file and give the write ' .
               'right to the web server (preferred solution), or give the ' .
               'write right to the web server on the <code>lib</code> ' .
               'directory.') .
             '</p></div>';
        require (JIRAFEAU_ROOT . 'lib/template/footer.php');
        exit;
    }
}

if (!is_writable (JIRAFEAU_CFG) && !@chmod (JIRAFEAU_CFG, '0666'))
{
    require (JIRAFEAU_ROOT . 'lib/template/header.php');
    echo '<div class="error"><p>' .
         _('The local configuration is not writable by the web server. ' .
           'Give the write right to the web server on the ' .
           '<code>lib/config.local.php</code> file.') .
         '</p></div>';
    require (JIRAFEAU_ROOT . 'lib/template/footer.php');
    exit;
}

if (isset ($_POST['step']) && isset ($_POST['next']))
{
    switch ($_POST['step'])
    {
    case 1:
        $cfg['lang'] = $_POST['lang'];
        jirafeau_export_cfg ($cfg);
        break;

    case 2:
        $cfg['web_root'] = jirafeau_add_ending_slash ($_POST['web_root']);
        $cfg['var_root'] = jirafeau_add_ending_slash ($_POST['var_root']);
        jirafeau_export_cfg ($cfg);
        break;

    default: break;
    }

}

require (JIRAFEAU_ROOT . 'lib/settings.php');
require (JIRAFEAU_ROOT . 'lib/template/header.php');

$current = 1;
if (isset ($_POST['next']))
    $current = $_POST['step'] + 1;
else if (isset ($_POST['previous']))
    $current = $_POST['step'] - 1;
else if (isset ($_POST['retry']))
    $current = $_POST['step'];

switch ($current)
{
case 3:
    ?><h2><?php printf (_('Installation of Jirafeau') . ' - ' . _('step') .
    ' %d ' . _('out of') . ' %d', 3, 3);
    ?></h2> <div id = "install"> <form action =
        "<?php echo basename(__FILE__); ?>" method = "post"> <input type =
        "hidden" name = "jirafeau" value =
        "<?php echo JIRAFEAU_VERSION; ?>" /><input type = "hidden" name =
        "step" value =
        "3" /><fieldset> <legend><?php echo _('Finalisation');
    ?></legend> <table> <tr> <td class = "info" colspan =
        "2"><?php echo
        _ ('Jirafeau is setting the website according to the configuration ' .
           'you provided.');
    ?></td> </tr> <tr> <td class = "nav previous"><input type =
        "submit" name = "previous" value =
        "
    <?php
    echo _('Previous step');
    ?>" /></td> <td></td> </tr>
        </table> </fieldset> </form> </div>
    <?php
    $err = jirafeau_check_var_dir ($cfg['var_root']);
    if ($err['has_error'])
    {
        echo '<div class="error"><p>'.$err['why'].'<br />'.NL;
        ?><form action = "<?php echo basename(__FILE__); ?>" method =
            "post"> <input type = "hidden" name = "jirafeau" value =
            "<?php echo JIRAFEAU_VERSION; ?>" /><input type = "hidden" name =
            "step" value = "3" /><input type = "submit" name =
            "retry" value =
            "<?php echo _('Retry this step'); ?>" /></form>
            <?php echo '</p></div>';
    }
    else
    {
        echo '<div class="message"><p>' .
             _('Jirafeau is now fully operational') . ':' .
             '<br /><a href="' . $cfg['web_root'] . '">' .
             $cfg['web_root'].'</a></p></div>';
    }
    break;

case 2:
    ?><h2><?php printf (_('Installation of Jirafeau') . ' - ' . _('step') .
    ' %d ' . _('out of') . ' %d', 2, 3);
    ?></h2> <div id = "install"> <form action =
        "<?php echo basename(__FILE__); ?>" method = "post"> <input type =
        "hidden" name = "jirafeau" value =
        "<?php echo JIRAFEAU_VERSION; ?>" /><input type = "hidden" name =
        "step" value =
        "2" /><fieldset> <legend><?php echo _('Information');
    ?></legend> <table> <tr> <td class = "info" colspan =
        "2"><?php echo
        _
        ('The base address of Jirafeau is the first part of the URL, until (and including) the last slash. For example: "http://www.example.com/". Do not forget the ending slash!');
    ?></td> </tr> <tr> <td class = "label"><label for = "input_web_root"
       ><?php echo _('Base address') . ':';
    ?></label></td>
        <td class = "field"><input type = "text" name = "web_root" id = "input_web_root" value = "<?php
        echo (empty($cfg['web_root']) ?
          'http://' . $_SERVER['HTTP_HOST'] . str_replace(basename(__FILE__), '', $_SERVER['REQUEST_URI']) :
          $cfg['web_root']);
      ?>" size = "40" /></td>
        </tr> <tr> <td class = "info" colspan = "2"><?php echo _('The data directory is where your files and information about your files will be stored. You should put it outside your web site, or at least restrict the access of this directory. Do not forget the ending slash!');
    ?></td> </tr> <tr> <td class = "label"><label for = "input_var_root"
       ><?php echo _('Data directory') . ':';
    ?></label></td>
        <td class = "field"><input type = "text" name = "var_root" id = "input_var_root" value = "<?php
        if(empty($cfg['var_root'])) {
          $alphanum = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
          $len_alphanum = strlen($alphanum);
          $var = 'var-';
          for($i = 0; $i <JIRAFEAU_VAR_RAND_LENGTH; $i++) {
            $var .= substr($alphanum, mt_rand(0, $len_alphanum - 1), 1);
          }
          echo JIRAFEAU_ROOT . $var . '/';
        } else {
          echo $cfg['var_root'];
        } 
      ?>" size = "40" /></td>
        </tr> <tr> <td colspan = "2"> <input type = "submit" class = "navright" name = "next" value = "<?php echo _('Next step'); ?>" /><input type = "submit" class = "navleft" name = "previous" value = "<?php echo _('Previous step'); ?>" /></td> </tr> </table> </fieldset> </form> </div> <?php break;

case 1:
default:
    ?><h2><?php printf (_('Installation of Jirafeau') . ' - ' . _('step') .
    ' %d ' . _('out of') . ' %d', 1, 3);
    ?></h2> <div id = "install"> <form action =
        "<?php echo basename(__FILE__); ?>" method = "post"> <input type =
        "hidden" name = "jirafeau" value =
        "<?php echo JIRAFEAU_VERSION; ?>" /><input type = "hidden" name =
        "step" value = "1" /><fieldset> <legend><?php echo _('Language');
    ?></legend> <table> <tr> <td class = "info" colspan =
        "2"><?php echo
        _
        ('Jirafeau is internationalised. Choose a specific langage or choose Automatic (langage is provided by user\'s browser).');
    ?></td> </tr> <tr> <td class = "label"><label for = "select_lang"
       ><?php echo _('Choose the default language') . ':';
    ?></label></td>
        <td class = "field">
        <select name = "lang" id = "select_lang">
        <?php foreach ($languages_list as $key => $item)
    {
        echo '<option value="'.$key.'"'.($key ==
                                         $cfg['lang'] ? ' selected="selected"'
                                         : '').'>'.$item.'</option>'.NL;
    }
    ?></select>
        </td>
        </tr>
        <tr class = "nav">
        <td></td>
        <td class = "nav next"><input type = "submit" name = "next" value =
        "<?php echo _('Next step'); ?>" /></td> </tr> </table>
        </fieldset> </form> </div> <?php break;
}


require (JIRAFEAU_ROOT . 'lib/template/footer.php');
?>
