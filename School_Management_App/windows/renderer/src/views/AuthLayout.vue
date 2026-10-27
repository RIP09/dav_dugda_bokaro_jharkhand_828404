<template>
  <div class="auth">
    <section class="art">
      <div class="blob b1" /><div class="blob b2" /><div class="blob b3" />
      <div class="art-in">
        <div class="mark">DAV</div>
        <h1>DAV Public School, Dugda</h1>
        <p>Bokaro, Jharkhand 828404</p>
        <ul class="points">
          <li><span class="material-icons">school</span>Students, parents, teachers & staff in one place</li>
          <li><span class="material-icons">payments</span>Fees, receipts, payroll & accounts</li>
          <li><span class="material-icons">fact_check</span>Attendance, exams, report cards & certificates</li>
          <li><span class="material-icons">lock</span>Every change recorded · permanent records locked</li>
        </ul>
        <div class="principal">Principal: <b>Prasanjeet Kumar Paul</b></div>
      </div>
    </section>
    <section class="form-side"><div class="form-in fade-up"><slot /></div></section>
  </div>
</template>
<style scoped>
.auth { display: flex; height: 100vh; background: #fff; }
.art { flex: 1.1; position: relative; overflow: hidden; background: linear-gradient(140deg, #0b1023 0%, #1e1b4b 55%, #3b0764 100%); color: #e0e7ff; display: flex; align-items: center; justify-content: center; }
.art-in { position: relative; max-width: 460px; padding: 40px; }
.mark { width: 64px; height: 64px; border-radius: 18px; background: linear-gradient(135deg, #f59e0b, #ef4444); display: grid; place-items: center; font-weight: 800; color: #fff; font-size: 18px; margin-bottom: 26px; box-shadow: 0 16px 40px -10px rgba(239,68,68,.6); }
h1 { font-size: 32px; color: #fff; line-height: 1.15; } p { opacity: .75; margin: 8px 0 28px; }
.points { list-style: none; padding: 0; margin: 0 0 30px; display: grid; gap: 14px; }
.points li { display: flex; gap: 12px; align-items: center; font-size: 14.5px; animation: fadeUp .6s var(--ease) both; }
.points li:nth-child(2) { animation-delay: .1s; } .points li:nth-child(3) { animation-delay: .2s; } .points li:nth-child(4) { animation-delay: .3s; }
.points .material-icons { width: 36px; height: 36px; border-radius: 11px; background: rgba(255,255,255,.1); display: grid; place-items: center; font-size: 19px; color: #c7d2fe; }
.principal { font-size: 13.5px; padding: 12px 16px; border-radius: 12px; background: rgba(255,255,255,.08); display: inline-block; }
.principal b { color: #fff; }
.blob { position: absolute; border-radius: 50%; filter: blur(60px); opacity: .55; animation: drift 14s ease-in-out infinite alternate; }
.b1 { width: 380px; height: 380px; background: #6366f1; top: -100px; left: -80px; }
.b2 { width: 320px; height: 320px; background: #db2777; bottom: -90px; right: -60px; animation-delay: -5s; }
.b3 { width: 240px; height: 240px; background: #f59e0b; top: 45%; left: 55%; opacity: .25; animation-delay: -9s; }
@keyframes drift { to { transform: translate(40px, 30px) scale(1.12); } }
.form-side { flex: 1; display: flex; align-items: center; justify-content: center; padding: 30px; overflow: auto; }
.form-in { width: 400px; max-width: 100%; }
</style>
