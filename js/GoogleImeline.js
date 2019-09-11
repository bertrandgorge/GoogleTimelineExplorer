document.getElementById('load-cookiedata-form').onsubmit = function() {

    var form = $(this);
    $('#load-cookiedata-form-error').html('');
    $('#load-cookiedata-form-error').hide();

    $.ajax({
        type: "POST",
        url: 'php/loadCookie.php',
        data: form.serialize(), // serializes the form's elements.
        success: function(data)
        {
            if (data == 'ok')
            {
                $('#load-data-page').show('fast');
                $('#intro-page').hide('slow');
            }
            else
            {
                $('#load-cookiedata-form-error').html(data);
                $('#load-cookiedata-form-error').show();
            }
        }
      });

    return false;
};

document.getElementById('load-data-form').onsubmit = function() {

    var form = $(this);
    $('#load-data-form-error').html('');
    $('#load-data-form-error').hide();
/*
    var intervalID = window.setInterval(function()
    {
        $.ajax({
            type: "POST",
            url: 'php/getFetchProgress.php',
            success: function(data)
            {
                if (isNumber(data))
                {
                    $('#load-data-form-progress').width = data;
                    $('#load-data-form-progress').show();
                }
            }
          });

    }, 2000);
*/
    $.ajax({
        type: "POST",
        url: 'php/fetchAllKML.php',
        success: function(data)
        {
            if (data == 'ok')
            {
                $('#app-page').show('fast');
                $('#load-data-page').hide('slow');
            }
            else
            {
                $('#load-data-form-error').html(data);
                $('#load-data-form-error').show();
            }

            clearInterval(intervalID);
        }
      });


    return false;
}

var currentDate = new Date();
$('#button-prev-month').click(function() {
    currentDate.setMonth(currentDate.getMonth()-1);
    recalcIndicators();
    return false;
});

$('#button-next-month').click(function() {
    currentDate.setMonth(currentDate.getMonth()+1);
    recalcIndicators();
    return false;
});

function recalcIndicators()
{
    var options = {month: "long"};
    $('#button-current-month').html(currentDate.toLocaleString("en-GB", options));

    $.ajax({
        type: "POST",
        dataType: "json",
        url: 'php/getMonthData.php',
        data: {date: currentDate.toISOString()}, // serializes the form's elements.
        success: function(data)
        {
            console.log(data);

            myPieChart = new Chart(ctx, {
                type: 'doughnut',
                data: $data['pieChartData'],
                options: {
                  maintainAspectRatio: false,
                  tooltips: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    caretPadding: 10,
                  },
                  legend: {
                    display: false
                  },
                  cutoutPercentage: 80,
                },
              });
        }
      });

}