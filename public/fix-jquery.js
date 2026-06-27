// public/js/fix-jquery.js
(function() {
    // Vérifier si jQuery est déjà chargé
    if (typeof window.jQuery === 'undefined') {
        console.log('Chargement de jQuery...');
        var script = document.createElement('script');
        script.src = 'https://code.jquery.com/jquery-3.6.4.min.js';
        script.integrity = 'sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=';
        script.crossOrigin = 'anonymous';
        script.onload = function() {
            console.log('jQuery chargé avec succès');
            // Réinitialiser DataTables si nécessaire
            if (typeof $.fn.DataTable !== 'undefined') {
                $.fn.dataTable.ext.errMode = 'throw';
            }
        };
        document.head.appendChild(script);
    } else {
        console.log('jQuery déjà chargé, version:', $.fn.jquery);
    }
})();