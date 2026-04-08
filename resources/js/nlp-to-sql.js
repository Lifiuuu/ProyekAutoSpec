import { apiClient } from './apiClient';

const NLP_TO_SQL_ENDPOINT = import.meta.env.VITE_NLP_TO_SQL_ENDPOINT || '/nlp-to-sql';

function setStatus(text, tone = 'neutral') {
    const status = document.getElementById('nlpToSqlStatus');
    if (!status) {
        return;
    }

    status.textContent = text;
    status.dataset.tone = tone;
}

function setLoading(isLoading) {
    const button = document.getElementById('nlpToSqlGenerateButton');
    const buttonText = document.getElementById('nlpToSqlGenerateButtonText');

    if (button) {
        button.disabled = isLoading;
        button.classList.toggle('opacity-60', isLoading);
        button.classList.toggle('cursor-not-allowed', isLoading);
    }

    if (buttonText) {
        buttonText.textContent = isLoading ? 'Processing...' : 'Run NLP to SQL';
    }
}

function renderResult(payload) {
    const output = document.getElementById('nlpToSqlResult');
    if (!output) {
        return;
    }

    output.textContent = JSON.stringify(payload, null, 2);
}

async function runNlpToSql() {
    const prompt = document.getElementById('nlpToSqlPrompt');
    const dialect = document.getElementById('nlpToSqlDialect');

    if (!prompt || !dialect) {
        return;
    }

    const promptValue = prompt.value.trim();
    if (!promptValue) {
        setStatus('Prompt tidak boleh kosong.', 'error');
        return;
    }

    setLoading(true);
    setStatus('Mengirim prompt ke endpoint mock NLP...', 'info');

    try {
        const response = await apiClient.post(NLP_TO_SQL_ENDPOINT, {
            prompt: promptValue,
            dialect: dialect.value,
            limit: 5,
        });

        renderResult(response.data);
        setStatus('JSON response diterima dan siap dirender.', 'success');
    } catch (error) {
        const message = error?.response?.data?.error?.message || error?.message || 'Gagal memanggil Edge Function.';
        renderResult({ success: false, error: message });
        setStatus(message, 'error');
    } finally {
        setLoading(false);
    }
}

function mountNlpToSqlDemo() {
    const button = document.getElementById('nlpToSqlGenerateButton');
    if (!button) {
        return;
    }

    button.addEventListener('click', runNlpToSql);
}

document.addEventListener('DOMContentLoaded', mountNlpToSqlDemo);

window.mountNlpToSqlDemo = mountNlpToSqlDemo;