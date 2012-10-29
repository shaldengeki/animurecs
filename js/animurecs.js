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
      $(els[0]).bind( 'click.DT', { action: "previous" }, fnClickHandler );
      $(els[1]).bind( 'click.DT', { action: "next" }, fnClickHandler );
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
            .bind('click', function (e) {
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

function addTagToManage() {
  $.getScript("/tag.php?action=check_add&name=" + $('#tag_name').val());
}

function deleteTagRelationship(targetID, subjectName, relationType, listElt) {
  // submits an AJAX request to the tagETI interface to remove a tag relationship of type relationType between two tags, targetID and subjectName.
  // attaches the result to listElt.
  $.getJSON("tag.php?action=edit_relationship&method=delete&id=" + targetID + "&type=" + relationType + "&name=" + subjectName, function(result) {
      if (result.return_code == 1) {
        listElt.children("li:contains('" + subjectName + "')").each(function() {
          $(this).remove();
        });
      } else {
        var alertDiv = $("<div>").addClass('alert').addClass('alert-error').text("An error occurred while deleting. Please try again.");
        alertDiv.append($("<button>").attr("type", "button").attr("class", "close").attr("data-dismiss", "alert").text("×"));
        listElt.before(alertDiv);
      }
    }).error(function() {
      var alertDiv = $("<div>").addClass('alert').addClass('alert-error').text("An error occurred while deleting. Please try again.");
      alertDiv.append($("<button>").attr("type", "button").attr("class", "close").attr("data-dismiss", "alert").text("×"));
      listElt.before(alertDiv);
    });
}

function createTagRelationship(targetID, subjectName, relationType, formElt) {
  // submits an AJAX request to the tagETI interface to create a tag relationship of type relationType between two tags, targetID and subjectName.
  // attaches the result to formElt.
  $.getJSON("tag.php?action=edit_relationship&method=create&id=" + targetID + "&type=" + relationType + "&name=" + subjectName, function(result) {
      if (result.return_code == 1) {
        newTagLink = $("<a>").attr("href", "#").text(subjectName);
        newTagEntry = $("<li>").append(newTagLink);
        tagEntryDeleteButton = $("<button>").attr("type", "button").attr("class", "close").attr("data-dismiss", "alert").text("×").click(function() {
          deleteTagRelationship(targetID, subjectName, relationType, formElt.parent().parent());
        });
        newTagEntry.append(tagEntryDeleteButton);
        formElt.next().show();
        formElt.next().before(newTagEntry);
        formElt.remove();
      } else {
        $(formElt).children("button").first().removeClass('disabled');
        $(formElt).children("button").first().text("Save");
        var alertDiv = $("<div>").addClass('alert').addClass('alert-error').text("An error occurred while saving. Please try again.");
        alertDiv.append($("<button>").attr("type", "button").attr("class", "close").attr("data-dismiss", "alert").text("×"));
        formElt.before(alertDiv);
      }
    }).error(function() {
      $(formElt).children("button").first().removeClass('disabled');
      $(formElt).children("button").first().text("Save");
      var alertDiv = $("<div>").addClass('alert').addClass('alert-error').text("An error occurred while saving. Please try again.");
      alertDiv.append($("<button>").attr("type", "button").attr("class", "close").attr("data-dismiss", "alert").text("×"));
      formElt.before(alertDiv);
    });
}

function addTagLink(elt) {
  // adds an input field to allow the user to add a tag to this page.

  // figure out what sort of relationship we're dealing with.
  if ($(elt).hasClass('add-related-tag-link')) {
    var relationType = 'relation';
  } else if ($(elt).hasClass('add-forbidden-tag-link')) {
    var relationType = 'forbidden';
  } else if ($(elt).hasClass('add-dependency-tag-link')) {
    var relationType = 'dependency';
  } else {
    return;
  }

  // construct a new form element.
  var formID = 'add-' + relationType + '-tag-form';
  var tagListID = 'add-' + relationType + '-tag-list';
  var listEntryElt = '';
  var formElt = $('<form>').attr('id', formID).attr('class', 'form-search');
  var inputElt = $('<input>').attr('name', 'name').attr('type', 'text').attr('autocomplete', 'off').attr('class', 'search-query').keyup(function() {
    $.getJSON("tag.php?action=search_name&name=" + $(this).val(), function(tags) {
      $('#' + tagListID).empty();
      $.each(tags, function(key, val) {
        $('#' + tagListID).show();
        listEntryElt = $('<li>').attr("class", "tag-list").attr("value", val.id).text(val.name).click(function() {
          inputElt.val($(this).text());
          $(this).parent().empty();
        });
        $('#' + tagListID).append($(listEntryElt));
      });
    });
  });
  var listElt = $('<ul>').attr('id', tagListID).attr('class', 'add-tag-list').hide();
  var submitElt = $('<button>').attr('class', 'btn').attr('type', 'button').text("Save").click(function() {
    $(this).addClass('disabled');
    $(this).text("Saving...");
    var targetID = $("h1").first().attr("tagID");
    createTagRelationship(targetID, inputElt.val(), relationType, formElt);
  });
  $(formElt).append($(inputElt)).append($(submitElt)).append($(listElt));
  $(elt).before($(formElt));
  $(elt).hide();
}

function removeTagLink(elt) {
  // removes a tag relationship from this tag.

  // figure out what sort of relationship we're dealing with.
  if ($(elt).hasClass('remove-related-tag-link')) {
    var relationType = 'relation';
  } else if ($(elt).hasClass('remove-forbidden-tag-link')) {
    var relationType = 'forbidden';
  } else if ($(elt).hasClass('remove-dependency-tag-link')) {
    var relationType = 'dependency';
  } else {
    return;
  }
  // construct a new form element.
  var formID = 'remove-' + relationType + '-tag-form';
  var tagListID = 'remove-' + relationType + '-tag-list';
  var targetID = $("h1").first().attr("tagID");
  var subjectName = $(elt).prev().text();
  var listElt = $(elt).parent().parent();
  deleteTagRelationship(targetID, subjectName, relationType, listElt);
}

$(document).ready(function () {
  $('.dropdown-toggle').dropdown();
  /* Table initialisation */
  $('.dataTable').each(function() {
    // see if there's a default-sort column. if not, default to the first column.
    defaultSortColumn = $(this).find('thead > tr > th.dataTable-default-sort').index('.dataTable > thead > tr > th');
    if (defaultSortColumn == -1) {
      defaultSortColumn = 0;
      defaultSortOrder = "asc";
    } else {
      defaultSortOrder = $(this).find('thead > tr > th.dataTable-default-sort').attr("data-sort-order");
    }
    if(typeof $(this).attr('data-recordsPerPage') != 'undefined') {
      recordsPerPage = $(this).attr('data-recordsPerPage');
    } else {
      recordsPerPage = 25;
    }
    $(this).dataTable({
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
  });
  if ($('#vis').length > 0) {
    drawLargeD3Plot();
  }
  $('#add-tag-to-manage').click(function() {
    addTagToManage();
  });
  $('.btn').each(function() {
    $(this).click( function() {
      $(this).unbind('click');
      $(this).addClass("disabled");
      $(this).text("Loading...");
    });
  });
  $('.add-tag-link').each(function() {
    $(this).click(function() {
      addTagLink(this);
    });
  });
  $('.remove-tag-link').each(function() {
    $(this).click(function() {
      removeTagLink(this);
    });
  });
  $('.token-input').each(function() {
    $(this).tokenInput($(this).attr("data-url"), {
      minChars: 3,
      theme: "facebook",
      preventDuplicates: true,
      propertyToSearch: $(this).attr("data-field"),
      prePopulate: $.secureEvalJSON($(this).attr("data-value"))
    });
  });
});