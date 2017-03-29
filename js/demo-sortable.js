(function($){ $(function(){
  $(".dash-summary > div").sortable({
    connectWith:".dash-summary > div",
    activate: function(event, ui) {
      $(this).css('border', '1px dashed #000')
        .css('background', '#ddd')
        .css('min-height', '4em');
    },
    deactivate: function (event, ui) {
      $(this).removeAttr('style');
    },
    over: function(event, ui) {
      $(this).css('background', '#fdf');
    },
    out: function(event, ui) {
      var $this = $(this);
      if ($this.attr('style')) {
        $this.css('background', '#ddd');
      }
    }
  });
}) })(jQuery);
