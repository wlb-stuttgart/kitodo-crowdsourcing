
jQuery( document ).ready(function() {

    jQuery('#cancelEdit').on('click', function (evt) {
        window.history.back();
    });

    jQuery('#cancelNew').on('click', function (evt) {
        window.history.back();
    });

    jQuery('#searchCampaign').on('keyup', function (evt) {

        var searchInput = document.getElementById('searchCampaign').value.toLowerCase();
        var campaigns = document.querySelectorAll('.campaign');
        var noCampaigns = true;

        campaigns.forEach(function(campaign) {
            var name = campaign.getAttribute('data-name').toLowerCase();
            // Check if campaign matches search and filters
            var matchesSearch = name.includes(searchInput);
            if (matchesSearch) {
                campaign.style.display = '';
                noCampaigns = false;
            } else {
                campaign.style.display = 'none';
            }
        });

        if (noCampaigns) {
            document.getElementById('noCampaignsAlert').style.display = 'block';
        } else {
            document.getElementById('noCampaignsAlert').style.display = 'none';
        }

    })

});
