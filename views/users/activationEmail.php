<?php
  require_once($_SERVER['DOCUMENT_ROOT']."/global/includes.php");
  $this->app->check_partial_include(__FILE__);
?>
<html>
  <head>
    <title>Animurecs account activation</title>
  </head>
  <body style="background-color: rgb(234, 234, 234) !important; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif !important; font-size: 14px !important; color: #333333 !important;" bgcolor="rgb(234, 234, 234)">
    <div class="container" style="width: 600px; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; margin: 0 auto;">
      <div class="banner-top" style="-webkit-border-top-left-radius: 5px; -webkit-border-top-right-radius: 5px; -moz-border-radius-topleft: 5px; -moz-border-radius-topright: 5px; border-top-left-radius: 5px; border-top-right-radius: 5px; background-color: #278c00; color: white; margin: 0; padding: 10px;"><h1 style="margin: 0;">Welcome to Animurecs!</h1></div>
      <div class="content" style="background-color: white; -webkit-border-bottom-right-radius: 5px; -webkit-border-bottom-left-radius: 5px; -moz-border-radius-bottomright: 5px; -moz-border-radius-bottomleft: 5px; border-bottom-right-radius: 5px; border-bottom-left-radius: 5px; padding: 10px;">
        <h2>Dear <?php echo escape_output($this->username); ?>,</h2>
        <p>
          Thanks for signing up to Animurecs! To get started, you'll need to activate your account by clicking <a href='<?php echo Config::ROOT_URL.$this->url('activate', Null, ['code' => $this->activationCode()]); ?>'>here</a>. If you haven't signed up for an account, don't worry - you can safely disregard this email.
        </p>
        <h3>Getting Started</h3>
        <p>
          After you've activated your account, there's a bunch of first steps you can take. If you've got a MyAnimelist account, you can import your list by going to "MAL Import" in <?php echo $this->link('edit', 'your settings'); ?> and typing in your MAL username. Or, you can start adding entries to your list straightaway by going to your profile. Once you've filled it out a little, we'll start to generate some recommendations for you to help you find more anime you'll love!
        </p>
        <h3>A side note</h3>
        <p>
          Animurecs is still under pretty heavy development, so on occasion you'll (probably) run into some snags and bugs. If this happens, or if you've just got ideas about how to improve Animurecs, feel free to get in touch with me by <a href='https://animurecs.com/users/shaldengeki'>posting on my profile</a>, or messaging me on Twitter at <a href='http://twitter.com/guocharles'>@guocharles</a>. I'd love to hear from you, even if you don't have a problem to report!
        </p>
        <hr />
        <p>
          I hope you enjoy using Animurecs as much as I love building it!
        </p>
        <p>
          -- Shal Dengeki
        </p>
      </div>
    </div>
    <style type="text/css">
    body { background-color: rgb(234, 234, 234) !important; font-family: "Helvetica Neue",Helvetica,Arial,sans-serif !important; font-size: 14px !important; color: #333333 !important; }
    </style>
  </body>
</html>