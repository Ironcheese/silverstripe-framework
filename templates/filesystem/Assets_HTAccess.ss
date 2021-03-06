#
# Whitelist appropriate assets files.
# This file is automatically generated via File.allowed_extensions configuration
# See AssetAdapter::renderTemplate() for reference.
#

<IfModule mod_rewrite.c>
    SetEnv HTTP_MOD_REWRITE On
    RewriteEngine On

    # Disable PHP handler
    RewriteCond %{REQUEST_URI} .(?i:php|phtml|php3|php4|php5|inc)$
    RewriteRule .* - [F]

    # Allow error pages
    RewriteCond %{REQUEST_FILENAME} -f
    RewriteRule error[^\\/]*\.html$ - [L]

    # Block invalid file extensions
    RewriteCond %{REQUEST_URI} !\.(?i:<% loop $AllowedExtensions %>$Extension<% if not $Last %>|<% end_if %><% end_loop %>)$
    RewriteRule .* - [F]

    # Non existant files passed to requesthandler
    RewriteCond %{REQUEST_URI} ^(.*)$
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule .* ../framework/main.php?url=%1 [QSA]
</IfModule>
