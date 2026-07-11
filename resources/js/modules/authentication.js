window.quickCreateEventForm = function() {
    return {
        celebrantName: '',
        eventStartDate: '',
        eventEndDate: '',
        eventType: '',
        async submitForm() {
            // Here you would typically send the data to your server using fetch or axios
           const response = await fetch('/create-event', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    celebrant_name: this.celebrantName,
                    event_start_date: this.startDate,
                    event_end_date: this.sndDate,
                    event_type: this.eventType
                })
            });
        }
    }
}