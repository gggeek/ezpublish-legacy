<?php

include_once( 'lib/ezutils/classes/ezini.php' );

/*!
 \return the current language used.
*/
function ezcurrentLanguage()
{
    $locale =& eZLocale::instance();
    $language =& $locale->localeCode();
    return $language;
}

/*!
 Replaces keys found in \a $text with values in \a $arguments.
 If \a $arguments is an associative array it will use the argument
 keys as replacement keys. If not it will convert the index to
 a key looking like %n, where n is a number between 1 and 9.
 Returns the new string.
*/
function &ezinsertarguments( $text, $arguments )
{
    if ( is_array( $arguments ) )
    {
        $replaceList = array();
        foreach ( $arguments as $argumentKey => $argumentItem )
        {
            if ( is_int( $argumentKey ) )
                $replaceList['%' . ( ($argumentKey%9) + 1 )] = $argumentItem;
            else
                $replaceList[$argumentKey] = $argumentItem;
        }
        $text = strtr( $text, $replaceList );
    }
    return $text;
}

/*!
 Translates the source \a $source with context \a $context and optional comment \a $comment
 and returns the translation.
 Uses eZTranslatorMananger::translate() to do the actual translation.

 If the site.ini settings RegionalSettings/TextTranslation is set to disabled this function
 will only return the source text.
*/
$ini =& eZINI::instance();
if ( $ini->variable( 'RegionalSettings', 'TextTranslation' ) != 'disabled' )
{
    include_once( 'kernel/classes/ezextension.php' );
    include_once( 'lib/ezi18n/classes/eztranslatormanager.php' );
    include_once( 'lib/ezi18n/classes/eztstranslator.php' );

    function &ezi18n( $context, $source, $comment = null, $arguments = null )
    {
        return eZTranslateText( false, $context, $source, $comment, $arguments );
    }

    function &ezx18n( $extension, $context, $source, $comment = null, $arguments = null )
    {
        return eZTranslateText( $extension, $context, $source, $comment, $arguments );
    }

    function &eZTranslateText( $extension, $context, $source, $comment = null, $arguments = null )
    {
        $language =& ezcurrentLanguage();
        if ( $language == "eng-GB" ) // eng-GB does not need translation
            return ezinsertarguments( $source, $arguments );

        $file = 'translation.ts';
        $root = false;
        if ( $extension !== false )
        {
            $baseDir = eZExtension::baseDirectory();
            $extensionDir = $baseDir . '/' . $extension . '/translations';
            if ( file_exists( $extensionDir ) )
                $root = $extensionDir;
        }

        // translation.ts translation
        $ini =& eZINI::instance();
        $useCache = $ini->variable( 'RegionalSettings', 'TranslationCache' ) != 'disabled';
        eZTSTranslator::initialize( $language . '/' . $file, $root, $useCache );

        // Bork translation: Makes it easy to see what is not translated.
        // If no translation is found in the eZTSTranslator, a Bork translation will be returned.
        // Bork is different than, but similar to, eng-GB, and is enclosed in square brackets [].
        include_once( 'lib/ezi18n/classes/ezborktranslator.php' );
        eZBorkTranslator::initialize();

        $man =& eZTranslatorManager::instance();
        $trans =& $man->translate( $context, $source, $comment );
        if ( $trans !== null )
            return ezinsertarguments( $trans, $arguments );

        eZDebug::writeWarning( "No translation for file(translation.ts) in context($context): '$source' with comment($comment)", "ezi18n" );
        return ezinsertarguments( $source, $arguments );
    }
}
else
{
    function &ezi18n( $context, $source, $comment = null, $arguments = null )
    {
        return ezinsertarguments( $source, $arguments );
    }

    function &ezx18n( $extension, $context, $source, $comment = null, $arguments = null )
    {
        return ezinsertarguments( $source, $arguments );
    }
}

?>
