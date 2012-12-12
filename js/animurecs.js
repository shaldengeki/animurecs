/* Default class modification */
$.extend( $.fn.dataTableExt.oStdClasses, {
  "sWrapper": "dataTables_wrapper form-inline"
} );

/* API method to get paging information */
$.fn.dataTableExt.oApi.fnPagingInfo = function ( oSettings ) {
  return {
    "iStart":         oSettings._iDisplayStart,
    "iEnd":           oSettings.fnDisplayEnd(),
    "iLength":        oSettings._iDisplayLength,
    "iTotal":         oSettings.fnRecordsTotal(),
    "iFilteredTotal": oSettings.fnRecordsDisplay(),
    "iPage":          Math.ceil( oSettings._iDisplayStart / oSettings._iDisplayLength ),
    "iTotalPages":    Math.ceil( oSettings.fnRecordsDisplay() / oSettings._iDisplayLength )
  };
}

/* Bootstrap style pagination control */
$.extend( $.fn.dataTableExt.oPagination, {
  "bootstrap": {
    "fnInit": function( oSettings, nPaging, fnDraw ) {
      var oLang = oSettings.oLanguage.oPaginate;
      var fnClickHandler = function ( e ) {
        e.preventDefault();
        if ( oSettings.oApi._fnPageChange(oSettings, e.data.action) ) {
          fnDraw( oSettings );
        }
      };

      $(nPaging).addClass('pagination').append(
        '<ul>'+
          '<li class="prev disabled"><a href="#">&larr; '+oLang.sPrevious+'</a></li>'+
          '<li class="next disabled"><a href="#">'+oLang.sNext+' &rarr; </a></li>'+
        '</ul>'
      );
      var els = $('a', nPaging);
      $(els[0]).on( 'click.DT', { action: "previous" }, fnClickHandler );
      $(els[1]).on( 'click.DT', { action: "next" }, fnClickHandler );
    },

    "fnUpdate": function ( oSettings, fnDraw ) {
      var iListLength = 5;
      var oPaging = oSettings.oInstance.fnPagingInfo();
      var an = oSettings.aanFeatures.p;
      var i, j, sClass, iStart, iEnd, iHalf=Math.floor(iListLength/2);

      if ( oPaging.iTotalPages < iListLength) {
        iStart = 1;
        iEnd = oPaging.iTotalPages;
      }
      else if ( oPaging.iPage <= iHalf ) {
        iStart = 1;
        iEnd = iListLength;
      } else if ( oPaging.iPage >= (oPaging.iTotalPages-iHalf) ) {
        iStart = oPaging.iTotalPages - iListLength + 1;
        iEnd = oPaging.iTotalPages;
      } else {
        iStart = oPaging.iPage - iHalf + 1;
        iEnd = iStart + iListLength - 1;
      }

      for ( i=0, iLen=an.length ; i<iLen ; i++ ) {
        // Remove the middle elements
        $('li:gt(0)', an[i]).filter(':not(:last)').remove();

        // Add the new list items and their event handlers
        for ( j=iStart ; j<=iEnd ; j++ ) {
          sClass = (j==oPaging.iPage+1) ? 'class="active"' : '';
          $('<li '+sClass+'><a href="#">'+j+'</a></li>')
            .insertBefore( $('li:last', an[i])[0] )
            .on('click', function (e) {
              e.preventDefault();
              oSettings._iDisplayStart = (parseInt($('a', this).text(),10)-1) * oPaging.iLength;
              fnDraw( oSettings );
            } );
        }

        // Add / remove disabled classes from the static elements
        if ( oPaging.iPage === 0 ) {
          $('li:first', an[i]).addClass('disabled');
        } else {
          $('li:first', an[i]).removeClass('disabled');
        }

        if ( oPaging.iPage === oPaging.iTotalPages-1 || oPaging.iTotalPages === 0 ) {
          $('li:last', an[i]).addClass('disabled');
        } else {
          $('li:last', an[i]).removeClass('disabled');
        }
      }
    }
  }
} );

function initDataTable(elt) {
  // see if there's a default-sort column. if not, default to the first column.
  defaultSortColumn = $(elt).find('thead > tr > th').index($(elt).find('thead > tr > th.dataTable-default-sort'));
  if (defaultSortColumn == -1) {
    defaultSortColumn = 0;
    defaultSortOrder = "asc";
  } else {
    if (typeof $(elt).find('thead > tr > th.dataTable-default-sort').attr("data-sort-order") != 'undefined') {
      defaultSortOrder = $(elt).find('thead > tr > th.dataTable-default-sort').attr("data-sort-order");
    } else {
      defaultSortOrder = "asc";
    }
  }
  if(typeof $(elt).attr('data-recordsPerPage') != 'undefined') {
    recordsPerPage = $(elt).attr('data-recordsPerPage');
  } else {
    recordsPerPage = 25;
  }
  $(elt).dataTable({
    "sDom": "<'row-fluid'<'span6'l><'span6'f>r>t<'row-fluid'<'span6'i><'span6'p>>",
    "sPaginationType": "bootstrap",
    "oLanguage": {
      "sLengthMenu": "_MENU_ records per page"
    },
    "iDisplayLength": recordsPerPage,
    "bPaginate": false,
    "bFilter": false,
    "bInfo": false,
    "aaSorting": [[ defaultSortColumn, defaultSortOrder ]]
  });
}

function displayListEditForm(elt) {
  // converts a normal table list entry into a list edit form.
  // is called on the last cell of a list entry row, on the icon's link.

  $(elt).parent().parent().find('.listEntryTitle .listEntryStatus').removeClass('hidden');
  scoreNode = $(elt).parent().parent().find('.listEntryScore');
  score = $(scoreNode).html();
  if (score == "") {
    score = 0;
  } else {
    score = parseInt(score.split("/")[0]);
  }
  $(scoreNode).html("<div class='input-append'><input class='input-mini' name='anime_list[score]' type='number' min=0 max=10 step=1 value='"+score+"' /><span class='add-on'>/10</span></div>");

  episodeNode = $(elt).parent().parent().find('.listEntryEpisode');
  episodeText = $(episodeNode).html();
  if (episodeText == "") {
    episodes = 0;
    episodeTotal = 0;
  } else {
    episodes = parseInt(episodeText.split("/")[0]);
    episodeTotal = parseInt(episodeText.split("/")[1]);
  }
  $(episodeNode).html("<div class='input-append'><input class='input-mini' name='anime_list[episode]' type='number' min=0 step=1 value='"+episodes+"' /><span class='add-on'>/" + episodeTotal + "</span></div>");

  editNode = $(elt).parent();
  url = $(elt).attr('data-url');
  $(editNode).empty().append($('<a></a>').attr('href', '#').attr('data-url', url).addClass('btn btn-mini btn-primary').text("Update").click(function(event) {
    submitListUpdate(this);
    event.preventDefault();
  }));
}

function revertListEditForm(elt) {
  // converts a list edit form back to a normal table list entry.
  // is called on the last cell of a list entry row, on the button.
  url = $(elt).attr('data-url');
  rowNode = $(elt).parent().parent();
  buttonNode = $(elt).parent();

  $(rowNode).find('.listEntryTitle .listEntryStatus').addClass('hidden');

  scoreNode = $(elt).parent().parent().find('.listEntryScore');
  $(scoreNode).html(scoreNode.find('input').val() + "/10");

  episodeNode = $(elt).parent().parent().find('.listEntryEpisode');
  finishedEps = $(episodeNode).find('.input-append input').val();
  totalEps = $(episodeNode).find('.input-append .add-on').text();
  $(episodeNode).html(finishedEps + totalEps);

  $(buttonNode).empty().append($('<a></a>').attr('href', '#').attr('data-url', url).addClass('listEdit').append("<i></i>").addClass('icon-pencil').click(function(event) {
    displayListEditForm(this);
    event.preventDefault();
  }));
}

function submitListUpdate(elt) {
  // submits a list entry update.
  // is called on the last cell of a list entry row, on the button.

  tableNode = $(elt).parent().parent().parent().parent();
  rowNode = $(elt).parent().parent();

  $(elt).off('click');
  $(elt).addClass("disabled");
  $(elt).text("Updating...");

  user_id = parseInt($(tableNode).attr('data-id'));
  anime_id = parseInt($(rowNode).attr('data-id'));
  status = parseInt($(rowNode).find('.listEntryStatus').find('option:selected').val());
  score = parseInt($(rowNode).find('.listEntryScore').find('input').val());
  episode = parseInt($(rowNode).find('.listEntryEpisode').find('input').val());

  url = $(elt).attr('data-url');
  $.post(url, { "anime_list[user_id]": user_id, "anime_list[anime_id]": anime_id, "anime_list[status]": status, "anime_list[score]": score, "anime_list[episode]": episode}).complete(function() {
    revertListEditForm(elt);
  });
}

function initInterface(elt) {
  // initializes all interface elements and events within a given element.

  $(elt).find('.dropdown-toggle').dropdown();

  /* Datatable initialisation */
  $(elt).find('.dataTable').each(function() {
    initDataTable(this);
  });

  /* D3 plot initialization */
  if ($(elt).find('#vis').length > 0) {
    drawLargeD3Plot();
  }

  /* Disable buttons on click */
  $(elt).find('.btn').each(function() {
    $(this).click( function() {
      $(this).off('click');
      $(this).addClass("disabled");
      $(this).text("Loading...");
    });
  });

  /* token input initialization */
  $(elt).find('.token-input').each(function() {
    if(typeof $(this).attr('data-tokenLimit') != 'undefined') {
      tokenLimit = $(this).attr('data-tokenLimit');
    } else {
      tokenLimit = null;
    }
    if(typeof $(this).attr('data-value') != 'undefined') {
      prePopulated = $.secureEvalJSON($(this).attr('data-value'));
    } else {
      prePopulated = null;
    }
    $(this).tokenInput($(this).attr("data-url"), {
      queryParam: "term",
      minChars: 3,
      theme: "facebook",
      preventDuplicates: true,
      propertyToSearch: $(this).attr("data-field"),
      prePopulate: prePopulated,
      tokenLimit: tokenLimit
    });
  });

  /* autocomplete initialization */
  $(elt).find('.autocomplete').each(function() {
    if(typeof $(this).attr('data-valueField') != 'undefined') {
      valueField = $(this).attr('data-valueField');
    } else {
      valueField = "value";
    }
    if(typeof $(this).attr('data-labelField') != 'undefined') {
      labelField = $(this).attr('data-labelField');
    } else {
      labelField = "label";
    }
    if(typeof $(this).attr('data-outputElement') != 'undefined') {
      outputElement = $(this).attr('data-outputElement');
    } else {
      outputElement = $(this);
    }
    $(this).autocomplete({
        source: $(this).attr('data-url'),
        minLength: 3,
        response: function(event, ui) {
          $.each(ui.content, function() {
            $(this).attr("label", $(this).attr(labelField));
            $(this).attr("value", $(this).attr(valueField));
          });
        },
        select: function(event, ui) {
          $(this).val(ui.item.label);
          $(outputElement).val(ui.item.value);
          event.preventDefault();
        }
    });
  });

  /* ajax feed autoloading. */
  $(window).unbind("scroll");
  $(window).scroll(function() {
    $(elt).find('ul.ajaxFeed:visible').each(function() {
      if ($(this).children('li').length > 0 && $(this).height() <= ($(window).height() + $(window).scrollTop()) && typeof $(this).attr('loading') == 'undefined') {
        //get last-loaded MAL change and load more past this.
        $(this).attr('loading', 'true');
        var lastTime = $(this).find('.feedDate:last').attr('data-time');
        var anime_id = "";
        if (typeof $(this).attr('anime_id') == 'undefined') {
          anime_id = $(this).attr('anime_id');
        }
        var user_id = "";
        if (typeof $(this).attr('user_id') == 'undefined') {
          user_id = $(this).attr('user_id');
        }
        if ($(this).attr('data-url').indexOf('?') < 0) {
          joinChar = '?';
        } else {
          joinChar = '&';
        }
        var originalElt = this;
        $.ajax({
          url: $(this).attr('data-url') + joinChar + "maxTime=" + encodeURIComponent(lastTime) + ((anime_id != "" && !isNaN(parseInt(anime_id))) ? '&anime_id=' + parseInt(anime_id) : "") + ((user_id != "" && !isNaN(parseInt(user_id))) ? '&user_id=' + parseInt(user_id) : ""),
          data: {},
          success: function(data) {
            $(originalElt).append($(data).html());
            $(originalElt).removeAttr('loading');
          }
        });
      }
    });
  });

  /* ajax tab autoloading. */
  $(elt).find('.nav-tabs li.ajaxTab:visible').each(function() {
    $(this).click(function(e) {
      var thisTab = e.target // activated tab

      var remoteTarget = $(thisTab).parent().attr('data-url');
      var pageTarget = $(thisTab).attr('href');

      $(pageTarget).parent().children('.tab-pane').each(function() {
        $(this).removeClass('active');
      });

      if (typeof $(thisTab).parent().attr('loaded') == 'undefined' && typeof $(thisTab).parent().attr('loading') == 'undefined') {
        $(thisTab).parent().attr('loading', true);
        $(pageTarget).load(remoteTarget, function() {
          $(thisTab).parent().removeAttr('loading');
          $(thisTab).parent().attr('loaded', true);
          initInterface(pageTarget);
        }).addClass('active');
      }
    });
  });

  /* feed entry menu initialization */
  $(elt).find('.feedEntry').each(function() {
    $(this).hover(function() {
      $(this).find('.feedEntryMenu').removeClass('hidden');
    }, function() {
      $(this).find('.feedEntryMenu').addClass('hidden');
    });
  });

  /* animelist edit link events */
  $(elt).find(".listEdit").each(function() {
    // allows users to update entries from their list.
    $(this).click(function(event) {
      displayListEditForm(this);
      event.preventDefault();
    });
  });

  /* Load a specific tab if url contains a hash */
  var url = document.location.toString();
  if (url.match('#')) {
      $(elt).find('.nav-tabs a[href=#'+url.split('#')[1]+']').tab('show') ;
  }
}

$(document).ready(function () {
  initInterface(document);
});