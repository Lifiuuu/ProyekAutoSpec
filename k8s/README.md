# Kubernetes Deployment - ProyekAutoSpec

Folder ini berisi manifest lengkap untuk menjalankan aplikasi Laravel AutoSpec di Kubernetes.

## Isi manifest

- `namespace.yaml`: Namespace `semute`
- `configmap.yaml`: Konfigurasi non-rahasia aplikasi
- `secret.example.yaml`: Contoh secret (WAJIB diganti nilainya)
- `deployment-web.yaml`: Deployment web Laravel
- `service.yaml`: Service internal untuk web
- `ingress.yaml`: Ingress endpoint HTTP
- `deployment-worker.yaml`: Deployment queue worker
- `cronjob-scheduler.yaml`: CronJob scheduler Laravel
- `hpa-web.yaml`: Autoscaling deployment web
- `kustomization.yaml`: Entrypoint deploy dengan Kustomize

## Prasyarat

1. Image aplikasi sudah tersedia, default:
   - `ghcr.io/lifiuuu/proyekautospec:latest`
2. Ingress controller sudah terpasang (contoh: NGINX Ingress)
3. Secret asli sudah dibuat (jangan gunakan `secret.example.yaml` apa adanya)

## Deploy

```bash
kubectl apply -k k8s
```

## Buat secret produksi (contoh)

```bash
kubectl -n semute create secret generic autospec-secret \
  --from-literal=APP_KEY='base64:REPLACE_ME' \
  --from-literal=DB_USERNAME='REPLACE_ME' \
  --from-literal=DB_PASSWORD='REPLACE_ME' \
  --from-literal=GROQ_API_KEY='REPLACE_ME' \
  --from-literal=GOOGLE_CLIENT_ID='REPLACE_ME' \
  --from-literal=GOOGLE_CLIENT_SECRET='REPLACE_ME' \
  --dry-run=client -o yaml | kubectl apply -f -
```

## Verifikasi

```bash
kubectl -n semute get pods
kubectl -n semute get svc
kubectl -n semute get ingress
kubectl -n semute logs deploy/autospec-web
kubectl -n semute logs deploy/autospec-worker
```

## Catatan penting

- Baseline lokal menggunakan PostgreSQL lokal (`127.0.0.1:5432`, database `dbautospec`) pada file `.env`.
- Runtime cluster menggunakan endpoint tim hackathon dari `configmap.yaml`: `103.185.52.138:1185`, database `semute`.
- Contoh pada `secret.example.yaml` hanya placeholder. Nilai asli (DB, LLM, Google OAuth) harus diinjeksikan saat deploy dan tidak boleh dikomit.
- Untuk production, disarankan gunakan external secret manager (misalnya Sealed Secrets, External Secrets Operator, atau Vault).
- Gunakan image tag versi release (jangan terus-menerus `latest`) saat deploy ke production.
