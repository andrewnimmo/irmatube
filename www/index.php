<?php require_once "php/config.php" ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="keywords" content="IRMA, IRMATube, film, privacy, security">
  <meta name="description" content="Experimental IRMATube video streaming service">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="irma-web-server" value="https://privacybydesign.foundation/tomcat/irma_api_server/server/">
  <meta name="irma-api-server" value="https://privacybydesign.foundation/tomcat/irma_api_server/api/v2/">

  <title>IRMATube - Watch movies without others noticing it!</title>

  <link href="css/mosaic.css" rel="stylesheet" type="text/css" />
  <link href="css/irmatube.css" rel="stylesheet" type="text/css" />
  <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet" />

  <script src="node_modules/jquery/dist/jquery.min.js" type="text/javascript"></script>
  <script src="js/mosaic.1.0.1.min.js" type="text/javascript"></script>
  <script src="node_modules/mustache/mustache.min.js" type="text/javascript"></script>
  <script src="content/movies.js" type="text/javascript"></script>

  <script src="https://privacybydesign.foundation/tomcat/irma_api_server/client/irma.js" type="text/javascript" async></script>

  <script id="moviePlayerTpl" type="text/template">
    <div class="modal fade" tabindex="-1" role="dialog" id="video_div_{{id}}">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-body">
                    <video controls="controls" preload="none" id="video_{{id}}">
                        <source src="{{url}}?file={{id}}.webm&token={{token}}" type="video/webm">
                        <source src="{{url}}?file={{id}}.mp4&token={{token}}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" onclick="closeMovie('{{id}}')">Close</button>
                </div>
            </div>
        </div>
    </div>
  </script>
  <script id="movieTpl" type="text/template">
    <div id="movie_{{id}}_wrapper">
    <div class="mosaic-block bar" id="movie_{{id}}">
      <span href="#"  class="mosaic-overlay">
        <h4 onclick="openMovie('{{id}}', '{{ageLimit}}');">{{title}}</h4>
        {{#ageLimit}}
        <a href='#ageModal' onclick="showAgeModal()">
          <img src='img/movieage-{{ageLimit}}.png' />
        </a>
        {{/ageLimit}}
      </span>

      <div class="mosaic-backdrop">
        <img alt="{{title}}" src="content/{{id}}.jpg">
      </div>
    </div>
    </div>
  </script>

  <script type="text/javascript">
    function showAgeModal() {
      $("#alert_box").empty();
      $("#ageModal").modal();
    };

    function showWarning(msg) {
      console.log(msg);
      $("#alert_box").html('<div class="alert alert-warning" role="alert">'
                           + '<strong>Warning:</strong> '
                           + msg + '</div>');
    };

    function showError(msg) {
      console.log(msg);
      $("#alert_box").html('<div class="alert alert-danger" role="alert">'
                           + '<strong>Error:</strong> '
                           + msg + '</div>');
    };

    function showSuccess(msg) {
      $("#alert_box").html('<div class="alert alert-success" role="alert">'
                           + '<strong>Success:</strong> '
                           + msg + '</div>');
    }

    function register() {
      console.log("Registring for IRMAtube");
      $("#alert_box").empty();
      var onIssuanceSuccess = function() {
          showSuccess("You are now registered for IRMATube");
      }

      $.get({
          url: "php/jwt.php?type=issuance&" + Math.random(), // Append randomness so that IE doesn't consider it 304 not modified
          success: function(jwt) {
              IRMA.issue(jwt, onIssuanceSuccess, showWarning, showError);
          }
      });
    }

    function openMovie(videoNumber, ageLimit) {
      console.log("Playing movie", videoNumber, ageLimit);
      $("#alert_box").empty();

      var onVerifySuccess = function(data) {
        console.log(data);

        var data = {
          id: videoNumber,
          token: data,
          url: "php/download.php"
        };

        var video_template = $("#moviePlayerTpl").html();
        $("#moviebox").html(Mustache.to_html(video_template, data));

        $("#video_div_" + videoNumber).modal('show')
        $("#video_div_" + videoNumber).css("display", "block");
        $("#video_" + videoNumber).get(0).load();
        $("#video_" + videoNumber).get(0).play();
      };

      var url = "php/jwt.php?type=verification";
      if (ageLimit > 0)
        var url = url + "&age=" + ageLimit;
      url = url + "&" + Math.random(); // Append randomness so that IE doesn't consider it 304 not modified

      $.get({
          url: url,
          success: function(jwt) {
              IRMA.verify(jwt, onVerifySuccess, showWarning, showError);
          }
      });
    }

    function closeMovie(videoNumber) {
      $("#video_div_" + videoNumber).modal('hide');
      $("#video_" + videoNumber).get(0).pause();
    }

    $(function() {
      var template = $("#movieTpl").html();
      IRMATubeMovies.sort(function() { return 0.5 - Math.random();});
      console.log(IRMATubeMovies);
      for ( var i = 0; i < IRMATubeMovies.length; i++) {
        movie = IRMATubeMovies[i];
        console.log(movie);
        $("#movies").append(Mustache.to_html(template, movie));
        $("#movie_" + movie.id).mosaic({
          animation : 'slide'
        });
      }
      $("#IRMARegister").on("click", register);
    });
  </script>
</head>

<body>
  <div id="moviebox">
  </div>
  <div id="irmaTube">
  <br>
  <div id="registerModal" class="modal fade" tabindex="-1" role="dialog"
    aria-labelledby="registerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal"
            aria-hidden="true">×</button>
          <h4 class="modal-title" id="registerModalLabel">Register for IRMATube</h4>
        </div>
        <div class="modal-body">
          <p>
          You can now register for IRMATube using your IRMA Token. You will get access to:
          <ol>
            <li>Eight splendid movie-trailers</li>
            <li>Automatic IRMA age verification</li>
          </ol>
          Best of all, it is totally
          </p>
          <p class="text-center">
            <h1 class="text-center">Free!</h1>
          </p>
        </div>
        <div class="modal-footer">
          <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
          <button class="btn btn-primary" data-dismiss="modal" aria-hidden="true" id="IRMARegister">Register using IRMA</button>
        </div>
      </div>
    </div>
  </div>

  <div id="ageModal" class="modal fade" tabindex="-1" role="dialog"
    aria-labelledby="ageModal" aria-hidden="true">
    <div class="modal-dialog" id="ageDialog">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal"
            aria-hidden="true">×</button>
          <h4 class="modal-title" id="ageModal">Age references</h4>
        </div>
        <div class="modal-body">
          <p>To watch movie trailers, you need to prove membership of the
            IRMA Tube club. Having your IRMA app at hand, you can do this
            easily.</p>

          <p>Don't forget that the IRMA technology is secure and respects
            your privacy. Neither central authorities nor the IRMA Tube service
            provider knows who you are, nobody can trace what movies you watch
            and you are interested in.</p>

          <table class="table">
            <tr>
              <td></td>
              <td>If the movie is not age restricted, you only need to show
                that you are a member.</td>
            </tr>
            <tr>
              <td><img src="img/movieage-12.png" /></td>
              <td>You need to show your 'Over 12' attribute and that you
                are a member</td>
            </tr>
            <tr>
              <td><img src="img/movieage-16.png" /></td>
              <td>You need to show your 'Over 16' attribute and that you
                are a member</td>
            </tr>
            <tr>
              <td><img src="img/movieage-18.png" /></td>
              <td>You need to show your 'Over 18' attribute and that you
                are a member</td>
            </tr>
          </table>
        </div>
        <div class="modal-footer">
          <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
        </div>
      </div>
    </div>
  </div>

  <div class="container">
    <div id="irmaTubeHeading" class="row">
      <div class="col-md-3">
        <a href="/demo"><img src="img/IRMATube_logo.png" width="200"/></a>
      </div>
      <div class="col-md-7">
        <div id="alert_box">
        </div>
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#registerModal">Register</button>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <div>
          <img src="img/arrows_blue_animated.gif" id="arrow" />
          IRMATube is the privacy-friendly video-streaming service
        </div>
      </div>
    </div>

    <div class="row">
      <div id="movies" class="col-sm-8 col-xs-12"></div>
      <div class="col-sm-4 col-xs-12">
        <?php require "explanation-$language.html" ?>
      </div>
    </div>
  </div>
  </div>
</body>
</html>