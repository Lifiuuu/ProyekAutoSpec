# nlp-to-sql

Supabase Edge Function scaffold for NLP to SQL.

## Local testing

1. Start the Supabase stack locally.
2. Serve the function with `supabase functions serve nlp-to-sql`.
3. Send a JSON POST request with `Authorization: Bearer <token>`.

## Sample request

```json
{
  "prompt": "Tampilkan daftar pesanan aktif",
  "dialect": "postgresql",
  "limit": 5
}
```

## Sample curl

```bash
curl -X POST http://localhost:54321/functions/v1/nlp-to-sql \
  -H "Authorization: Bearer dev-token" \
  -H "Content-Type: application/json" \
  -d '{"prompt":"Tampilkan daftar pesanan aktif","dialect":"postgresql","limit":5}'
```