<?php

  session_start();

  define('FORCE_GET', false);

  class Tools {

    public static function getJson (STRING $url) {
      $json = file_get_contents($url);
      return json_decode($json,true);
    }

    public static function checkInSession($name, $callback) {

      if (!isset($_SESSION[$name]) || FORCE_GET)
            $_SESSION[$name] = $callback();

      return $_SESSION[$name];

    }

    public static function getListOfSeason($list) {

      foreach($list as $k=>$l) {

        $list[$k]['url'] = str_replace(['__ID__'],[$l['id']],'https://player.pl/playerapi/product/vod/serial/40135/season/__ID__/episode/list?4K=true&platform=BROWSER');

        $list[$k]['list'] = self::getJson($list[$k]['url']);

      }

      return $list;

    }

    public static function getPersons($list) {

      $persons = Array();

      foreach($list as $s) {

        foreach ($s['list'] as $e) {

          $title = $e['title'];

          if(preg_match("#\s(-\s)?ciąg d(al|la)szy#i",$title))
            continue;

          $title = preg_replace('#\s(-\s)?(odcinek specjalny|u kuby!)#i','',$title);
          $title = str_replace(['”','„',', '],['"','"',','],$title);
          //$title = preg_replace('/[^"]? (i|oraz|vs) [^"]?/',',',$title);
          $title = preg_replace('/\b(i|oraz|vs)\b(?![^"]*"(?:(?:[^"]*"){2})*[^"]*$)/',',',$title);
          $persons_flat = explode(',',$title);

          foreach($persons_flat as $p) {

            $md5 = md5(trim($p));

            if (!isset($persons[$md5])) {
              $persons[$md5] = Array(
                'name' => trim($p),
                'episodes' => Array()
              );
            }

            $persons[$md5]['episodes'][] = $e;

          }

        }

      }

      return $persons;

    }


  }

  $seasons_list_url = 'https://player.pl/playerapi/product/vod/serial/40135/season/list?4K=true&platform=BROWSER';

  $seasons_list = Tools::checkInSession('listOfSeasons',function() use ($seasons_list_url) {return Tools::getJson($seasons_list_url);});
  $seasons_list = Tools::checkInSession('listOfSeasonsWithEpisode',function() use ($seasons_list){return Tools::getListOfSeason($seasons_list);});

  $persons = Tools::getPersons($seasons_list);

  $col = array_column( $persons, "name" );
  array_multisort( $col, SORT_STRING, $persons );

?>

<!DOCTYPE html>
<html lang="pl">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>Lista</title>
    <link rel="stylesheet" href="css/main.css" />
    <link rel="icon" href="images/favicon.png" />
  </head>

  <body>

    <!--<table>
      <tr>
        <td>Sezon</td>
        <td>Odcinek</td>
        <td>Premiera</td>
        <td>Tytuł</td>
      </tr>
      <?php
        foreach ($seasons_list as $s) {

          $mustPass = true;
          $str = '<td rowspan="'.(count($s['list'])).'">' . $s['display'] . '</td>';

          foreach($s['list'] as $e) {

            $str = '<tr>' . ($mustPass) ? $str : '';

            $mustPass = false;

            $str .= '<td>' . $e['episode'] . '</td>';
            $str .= '<td>' . $e['since'] . '</td>';
            $str .= '<td>' . $e['title'] . '</td>';


            $str .= '</tr>';

          }

          echo $str;

        }
      ?>
    </table>-->

    <table>
      <tr>
        <td>Osoba</td>
        <td>Odcinki</td>
      </tr>
      <?php
        foreach($persons as $p) {
          ?>
        <tr>
          <td><?php echo $p['name']; ?></td>
          <td>
            <ul>
              <?php
                foreach($p['episodes'] as $e) {
                  ?>
                  <li>
                    <a href="<?php echo $e['shareUrl'] ?>" target="_blank"><?php echo $e['title'] ?> (sezon <?php echo $e['season']['number'] ?>, odcinek <?php echo $e['episode'] ?>, <?php echo $e['since'] ?>)</a>
                  </li>
                  <?php
                }
              ?>
            </ul>
          </td>
        </tr>
          <?php
        }
      ?>
    </table>

    <script src="js/scripts.js"></script>
  </body>
</html>
