# Open Source Notice & Lineage Attribution

**Project:** Sistem Informasi Pondok Pesantren Fatimah Az-Zahra  
**Status:** Active Institutional Derivative System  
**Date:** September 2026  

---

## 1. Original Project Attribution

This software repository is a derivative project originating from and based upon:

- **Original Project:** Digitren (Sistem Informasi Pondok Pesantren)
- **Original Author & Maintainer:** Ahmad Muzayyin ([@AhmadMuzayyin](https://github.com/AhmadMuzayyin))
- **Original Source Repository:** [https://github.com/AhmadMuzayyin/digitren](https://github.com/AhmadMuzayyin/digitren)
- **Original License:** MIT License

We express our gratitude to Ahmad Muzayyin and the contributors of the original Digitren project for providing the foundational software structure.

---

## 2. MIT License Compliance

In accordance with the requirements of the MIT License, the original copyright notice and permission notice are preserved below:

```text
MIT License

Copyright (c) 2023 Ahmad Muzayyin

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## 3. Institutional Context & Purpose

This derivative repository was developed and customized specifically to serve the administrative, academic, residential, and financial ledger requirements of:

**Pondok Pesantren Fatimah Az-Zahra**  
Target Repository: [https://github.com/risky037/sistem-ponpes.git](https://github.com/risky037/sistem-ponpes.git)

---

## 4. Custom Modifications & Architectural Enhancements

Since diverging from the upstream Digitren repository, this project has implemented several major enhancements, bug fixes, and structural improvements:

1. **Security & Credential Hardening:**
   - Removal of hardcoded third-party API credentials, WhatsApp gateway tokens, and hardcoded Google Spreadsheet IDs from source code and git history.
   - Transition to strictly environment-backed configuration architecture.
2. **Data Model Normalization:**
   - Multi-table normalization for student addresses (`alamat_santris`), class assignments (`kelas_santris`), and room assignments (`kamar_santris`).
   - Indonesian administrative division integration (provinsi, kabupaten, kecamatan, kelurahan).
3. **Financial Ledger & Transaction Engine:**
   - Student savings ledger (`tabungans`) with debit/credit balance validation.
   - Internal peer-to-peer balance transfers between student accounts (`transfers`).
   - Transaction audit logging (`transaksi_tabungans`).
4. **Institutional Branding & KTS Generator:**
   - Custom identity and settings engine (`settings` table).
   - Dynamic Student Identity Card (KTS - *Kartu Tanda Santri*) generation with integrated barcode printing.
5. **Modernized Framework & CI/CD Roadmap:**
   - Formal branch architecture and GitHub Actions continuous integration.
   - Stepwise framework upgrade pipeline: Laravel 10 stabilization $\rightarrow$ Laravel 11 $\rightarrow$ Laravel 12 $\rightarrow$ Laravel 13.
