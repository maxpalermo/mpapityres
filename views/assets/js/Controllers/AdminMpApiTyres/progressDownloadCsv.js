// Funzione per avviare il download
function startDownload(url) {
    // Chiamata AJAX per iniziare il download
    $.ajax({
        url: url,
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Inizia a monitorare il progresso
                const progressId = response.progress_id;
                updateProgressBar(progressId);
                
                // Aggiorna il progresso ogni secondo
                const progressInterval = setInterval(function() {
                    updateProgressBar(progressId, progressInterval);
                }, 1000);
            } else {
                alert('Errore: ' + response.message);
            }
        },
        error: function() {
            alert('Errore durante l\'avvio del download');
        }
    });
}

// Funzione per aggiornare la barra di progresso
function updateProgressBar(url, progressId, interval) {
    let errorCount = 0;

    $.ajax({
        url: url,
        type: 'GET',
        data: { progress_id: progressId },
        dataType: 'json',
        success: function(progress) {
            // Aggiorna l'interfaccia utente con le informazioni sul progresso
            if (progress.status === 'downloading') {
                $('#progress-bar').css('width', progress.percent + '%');
                $('#progress-text').text(progress.percent + '% completato');
                $('#download-speed').text('Velocità: ' + progress.speed);
                $('#download-eta').text('Tempo rimanente: ' + progress.eta);
                $('#download-size').text('Dimensione: ' + progress.human_downloaded + ' / ' + progress.human_size);
            } else if (progress.status === 'completed') {
                $('#progress-bar').css('width', '100%');
                $('#progress-text').text('Download completato!');
                
                // Ferma l'intervallo
                if (interval) clearInterval(interval);
                
                // Mostra un messaggio di completamento
                $('#download-status').text('File scaricato con successo');
            } else if (progress.status === 'error') {
                $('#progress-text').text('Errore: ' + progress.message);
                
                // Ferma l'intervallo
                if (interval) clearInterval(interval);
            }
        },
        error: function() {
            // Gestisci gli errori
            $('#progress-text').text('Errore durante il controllo del progresso');
            
            // Ferma l'intervallo dopo alcuni tentativi falliti
            if (interval) {
                errorCount++;
                
                if (errorCount > 5) {
                    clearInterval(interval);
                }
            }
        }
    });
}