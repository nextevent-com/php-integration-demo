/*
 * Client utility script to connect the web shop with the NextEvent Widget API
 * 
 * This file is part of the NextEvent integration demo site and only serves as an example.
 * Please do not use in production.
 *
 * @ 2018 NextEvent AG - nextevent.com
 */

(function(NextEventWidgetAPI) {
  /**
   * Handler for 'basketUpdate' messages from NextEvent Widget API
   *
   * Saves the submitted order/basket ID in our shop's session
   *
   * @param {Object} data Message data {order_id: Number}
   */
  function basketUpdateCallback(data) {
    console.log('[API] basketUpdate message received', data);

    var postData = {set_order_id: data.order_id};
    $.ajax({
      method: 'POST',
      url: './server.php',
      data: postData,
    }).done(function(data) {
      // update the basket view if response contains HTML content
      var $basket = $('#basket');
      if (data.html) {
        $basket.html(data.html);
      }
      if (data.error) {
        $basket.append('<div class="alert alert-danger"><strong>ERROR</strong> ' + data.error + '</div>');
      }
    });
  }

  NextEventWidgetAPI.addMessageHandler('basketUpdate', basketUpdateCallback);

  /**
   * Handler for 'closeWidget' messages from NextEvent Widget API
   *
   * In this demo, this will redirect the user to the checkout page
   *
   * @param {Object} data Message data
   */
  function closeWidgetCallback(data) {
    console.log('[API] closeWidget message received', data);

    window.location.href = './checkout.php';
  }

  NextEventWidgetAPI.addMessageHandler('closeWidget', closeWidgetCallback);

  /**
   * Handler for 'timeout' messages from NextEvent Widget API
   *
   * In this demo, this will simply reload the widget embed page.
   *
   * @param {Object} data Message data
   */
  function widgetTimeoutCallback(data) {
    console.log('[API] timeout message received', data);

    window.location.reload();
  }

  NextEventWidgetAPI.addMessageHandler('timeout', widgetTimeoutCallback);


  /**
   * create ticket list
   *
   * @param {Array} urls
   */
  function showTickets(urls) {
    $('.load-msg').hide();

    var $list = $('.ticket-list');
    urls.forEach(function(url) {
      $list.append(
        '<div class="panel panel-default">' +
          '<div class="panel-body">' +
            '<a href="' + url + '"><i class="fa fa-ticket"></i> Download tickets</a>' +
          '</div>' +
        '</div>'
      );
    });

    $list.show();
  }

  // Start polling server for tickets to be issued.
  // This is an asynchronous process in NextEvent and therefore the integrating
  // application shall wait for tickets in the background.
  if (window.pollTickets) {
    var pollRetries = 0;
    var responseHandler = function(data) {
      if (data.ready) {
        if (window.refreshView) {
          window.location.reload();
        } else {
          showTickets(data.urls);
        }
      } else if (++pollRetries > 2) {
        $('.load-msg').html('<div class="panel-body">' + (data.message || 'No tickets found') + '</div>');
      } else {
        $.ajax('./server.php?tickets').done(responseHandler);
      }
    };

    $.ajax('./server.php?tickets').done(responseHandler);
  }
})(window.NextEventWidgetAPI);
