import React from 'react';
import { motion, useReducedMotion } from 'framer-motion';

function Reveal({ children, delay = 0 }) {
  const prefersReducedMotion = useReducedMotion();

  if (prefersReducedMotion) {
    return <div>{children}</div>;
  }

  return (
    <motion.div
      initial={{ opacity: 0, y: 22 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, amount: 0.2 }}
      transition={{ duration: 0.55, ease: 'easeOut', delay }}
    >
      {children}
    </motion.div>
  );
}

function FeatureCard({ title, description, icon }) {
  return (
    <div className="rounded-2xl border border-[#234C6A]/60 bg-[#141414]/85 p-6 shadow-[0_10px_30px_rgba(0,0,0,0.28)] transition-transform duration-200 hover:-translate-y-1 hover:border-[#456882]">
      <p className="mb-3 text-2xl" aria-hidden="true">{icon}</p>
      <h3 className="text-lg font-semibold text-[#F7F8F0]">{title}</h3>
      <p className="mt-2 text-sm leading-6 text-[#F7F8F0]/82">{description}</p>
    </div>
  );
}

function DeliverableCard({ fileName, description }) {
  return (
    <div className="rounded-2xl border border-[#456882]/45 bg-[#0F1419]/90 p-5">
      <p className="text-xs font-semibold uppercase tracking-[0.14em] text-[#86B3D1]">Output</p>
      <h4 className="mt-2 text-base font-bold text-[#F7F8F0]">{fileName}</h4>
      <p className="mt-2 text-sm leading-6 text-[#F7F8F0]/80">{description}</p>
    </div>
  );
}

export default function LandingPage({ onLogin, onRegister }) {
  return (
    <div className="min-h-screen bg-[#1E1E1E] text-[#F7F8F0] font-['Sora',ui-sans-serif,system-ui,sans-serif]">
      <div className="pointer-events-none absolute inset-x-0 top-0 h-[480px] bg-[radial-gradient(circle_at_20%_10%,rgba(69,104,130,0.38),transparent_60%),radial-gradient(circle_at_80%_0%,rgba(35,76,106,0.28),transparent_45%)]" />

      <header className="sticky top-0 z-30 border-b border-white/10 bg-[#1E1E1E]/85 backdrop-blur-xl">
        <div className="mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
          <a href="#hero" className="flex items-center gap-3">
            <img src="/images/autospec-logo.svg" alt="AutoSpec" className="h-10 w-auto" />
            <span className="text-sm font-semibold tracking-[0.16em] text-[#F7F8F0]/95">AUTOSPEC</span>
          </a>

          <nav className="flex items-center gap-3 overflow-x-auto text-xs text-[#F7F8F0]/80 sm:gap-6 sm:text-sm">
            <a href="#fitur" className="whitespace-nowrap transition-colors hover:text-[#F7F8F0]">Fitur</a>
            <a href="#deliverables" className="whitespace-nowrap transition-colors hover:text-[#F7F8F0]">3 File Sakti</a>
            <a href="#cta" className="whitespace-nowrap transition-colors hover:text-[#F7F8F0]">Mulai</a>
          </nav>

          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={onLogin}
              className="rounded-xl border border-[#456882]/70 px-4 py-2 text-sm font-semibold text-[#F7F8F0] transition hover:border-[#86B3D1] hover:bg-[#456882]/30"
            >
              Login
            </button>
            <button
              type="button"
              onClick={onRegister}
              className="rounded-xl bg-[#456882] px-4 py-2 text-sm font-bold text-white shadow-lg shadow-[#456882]/25 transition hover:brightness-110"
            >
              Registrasi
            </button>
          </div>
        </div>
      </header>

      <main className="relative">
        <section id="hero" className="scroll-mt-28 mx-auto grid w-full max-w-6xl gap-10 px-4 pb-24 pt-16 sm:px-6 lg:grid-cols-[1.15fr_0.85fr] lg:px-8 lg:pt-24">
          <Reveal>
            <p className="inline-flex rounded-full border border-[#456882]/60 bg-[#234C6A]/30 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-[#A8D0E6]">
              High-Conversion Landing Experience
            </p>
            <h1 className="mt-6 text-4xl font-black leading-tight text-[#F7F8F0] sm:text-5xl lg:text-6xl">
              Dari Ide ke Backend Siap Pakai,
              <span className="block text-[#86B3D1]">Dalam Sekejap Dengan AutoSpec.</span>
            </h1>
            <p className="mt-6 max-w-2xl text-lg leading-8 text-[#F7F8F0]/82">
              AutoSpec mengubah deskripsi bahasa alami menjadi struktur database, spesifikasi API,
              dan koleksi pengujian yang siap dipakai tim engineering.
            </p>
            <div className="mt-9 flex flex-wrap gap-3">
              <button
                type="button"
                onClick={onRegister}
                className="rounded-2xl bg-[#456882] px-6 py-3 text-sm font-bold text-white shadow-xl shadow-[#456882]/30 transition hover:-translate-y-0.5 hover:brightness-110"
              >
                Mulai Sekarang
              </button>
              <button
                type="button"
                onClick={onLogin}
                className="rounded-2xl border border-[#456882]/70 bg-[#0F1419]/45 px-6 py-3 text-sm font-semibold text-[#F7F8F0] transition hover:border-[#86B3D1]"
              >
                Login
              </button>
            </div>
          </Reveal>

          <Reveal delay={0.1}>
            <div className="rounded-3xl border border-[#234C6A]/70 bg-[#141414]/90 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.35)]">
              <p className="text-xs font-semibold uppercase tracking-[0.2em] text-[#9FC9E4]">Semute Edition</p>
              <div className="mt-4 space-y-3">
                {[
                  'Natural language prompt menjadi schema siap migrate',
                  'OpenAPI JSON untuk integrasi backend cepat',
                  'Collection API test untuk validasi kolaboratif',
                ].map((item) => (
                  <div key={item} className="rounded-xl border border-[#234C6A]/55 bg-[#1E1E1E] p-3 text-sm text-[#F7F8F0]/90">
                    {item}
                  </div>
                ))}
              </div>
            </div>
          </Reveal>
        </section>

        <section id="fitur" className="scroll-mt-28 mx-auto w-full max-w-6xl px-4 pb-20 sm:px-6 lg:px-8">
          <Reveal>
            <h2 className="text-3xl font-extrabold text-[#F7F8F0] sm:text-4xl">Fitur Unggulan</h2>
            <p className="mt-3 max-w-3xl text-base leading-7 text-[#F7F8F0]/75">
              Alur kerja AutoSpec dirancang agar tim produk, data, dan backend bisa bergerak lebih cepat
              tanpa kehilangan presisi desain.
            </p>
          </Reveal>
          <div className="mt-8 grid gap-4 sm:grid-cols-2">
            <Reveal delay={0.05}><FeatureCard icon="⚡" title="Instant Infrastructure" description="Bentuk pondasi database dari ide produk tanpa setup berulang." /></Reveal>
            <Reveal delay={0.1}><FeatureCard icon="🧠" title="AI-Driven Precision" description="Validasi hasil generate dengan pendekatan terstruktur dan jelas." /></Reveal>
            <Reveal delay={0.15}><FeatureCard icon="🧩" title="Multi-Schema Isolation" description="Pisahkan domain data agar tetap rapi untuk scaling jangka panjang." /></Reveal>
            <Reveal delay={0.2}><FeatureCard icon="☁️" title="Cloud Native" description="Hasil siap diintegrasikan ke pipeline deployment modern." /></Reveal>
          </div>
        </section>

        <section id="deliverables" className="scroll-mt-28 mx-auto w-full max-w-6xl px-4 pb-20 sm:px-6 lg:px-8">
          <Reveal>
            <h2 className="text-3xl font-extrabold text-[#F7F8F0] sm:text-4xl">3 File Sakti</h2>
            <p className="mt-3 max-w-3xl text-base leading-7 text-[#F7F8F0]/75">
              Sekali generate, tim langsung memperoleh artefak backend yang paling dibutuhkan.
            </p>
          </Reveal>
          <div className="mt-8 grid gap-4 lg:grid-cols-3">
            <Reveal delay={0.05}><DeliverableCard fileName="database.sql" description="DDL dan struktur tabel siap review serta migrasi." /></Reveal>
            <Reveal delay={0.1}><DeliverableCard fileName="openapi.json" description="Kontrak API terstandar untuk kolaborasi frontend-backend." /></Reveal>
            <Reveal delay={0.15}><DeliverableCard fileName="postman_collection.json" description="Skenario request siap uji untuk validasi cepat endpoint." /></Reveal>
          </div>
        </section>

        <section id="cta" className="scroll-mt-28 mx-auto w-full max-w-6xl px-4 pb-24 sm:px-6 lg:px-8">
          <Reveal>
            <div className="rounded-3xl border border-[#456882]/70 bg-[linear-gradient(135deg,#141414,#1A1F2E)] p-8 shadow-[0_20px_60px_rgba(0,0,0,0.35)] sm:p-10">
              <h2 className="text-3xl font-extrabold text-[#F7F8F0] sm:text-4xl">Siap Mempercepat Workflow Backend Timmu?</h2>
              <p className="mt-4 max-w-3xl text-base leading-7 text-[#F7F8F0]/80">
                Mulai dari sekarang dan ubah brainstorming produk menjadi implementasi teknis yang siap dijalankan.
              </p>
              <div className="mt-8 flex flex-wrap gap-3">
                <button
                  type="button"
                  onClick={onRegister}
                  className="rounded-2xl bg-[#456882] px-6 py-3 text-sm font-bold text-white transition hover:brightness-110"
                >
                  Mulai Sekarang
                </button>
                <button
                  type="button"
                  onClick={onLogin}
                  className="rounded-2xl border border-[#456882]/70 px-6 py-3 text-sm font-semibold text-[#F7F8F0] transition hover:border-[#86B3D1]"
                >
                  Login
                </button>
              </div>
            </div>
          </Reveal>
        </section>
      </main>

      <footer className="border-t border-white/10 bg-[#141414]">
        <div className="mx-auto flex w-full max-w-6xl flex-col gap-3 px-4 py-6 text-sm text-[#F7F8F0]/70 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
          <p>AutoSpec by Semute Team · Refactory Hackathon UNAIR</p>
          <div className="flex flex-wrap items-center gap-4">
            <a href="#fitur" className="transition-colors hover:text-[#F7F8F0]">Fitur</a>
            <a href="#deliverables" className="transition-colors hover:text-[#F7F8F0]">Output</a>
            <button type="button" onClick={onLogin} className="transition-colors hover:text-[#F7F8F0]">Login</button>
            <button type="button" onClick={onRegister} className="transition-colors hover:text-[#F7F8F0]">Registrasi</button>
          </div>
        </div>
      </footer>
    </div>
  );
}