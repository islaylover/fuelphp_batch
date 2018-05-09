<?php
/**
 * Use this file to override global defaults.
 *
 * See the individual environment DB configs for specific config information.
 */

return array(
  'liquor' => array(
             'type'        => 'pdo',
            'connection'  => array(
                    'dsn'        => 'mysql:host=localhost;dbname=spirits',
                    'username'   => 'osake',
                    'password'   => 'daisuki1234',
            ),
  ),
);
