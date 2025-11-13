document.addEventListener('DOMContentLoaded', function() {
    const chatgptBtn = document.querySelector('.ai-btn-chatgpt');
    const perplexityBtn = document.querySelector('.ai-btn-perplexity');
    
    if (chatgptBtn) {
        chatgptBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.dataset.url;
            const prompt = encodeURIComponent(`Please provide a comprehensive summary of this article:\n\n${url}`);
            window.open(`https://chatgpt.com?q=${prompt}`, '_blank');
        });
    }
    
    if (perplexityBtn) {
        perplexityBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.dataset.url;
            const prompt = encodeURIComponent(`Please provide a comprehensive summary of this article:\n\n${url}`);
            window.open(`https://www.perplexity.ai?q=${prompt}`, '_blank');
        });
    }
});
