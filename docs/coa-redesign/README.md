# COA design review

Local design proposal, not deployed. Scientific fixtures are reviewed against the two original 30 mg PDFs and explicitly distinguished from unchanged saved records. See `../COA-REDESIGN-AUDIT-2026-09-08.md` for evidence, coverage and release limitations.

Open the running local preview at http://127.0.0.1:8874/docs/coa-redesign/ and use Past batch / Current batch at the top. To restart, serve the repository root with a static HTTP server on port 8874; ES modules require HTTP rather than opening the HTML directly from disk.

Approval images:

- `desktop-past.png`: 1440px historical batch.
- `mobile-past.png`: 390px historical batch.
- `desktop-current.png`: 1440px current batch.
- `mobile-current.png`: 390px current batch.

The header is a simplified brand shell. Reviewer-only fixture switches and discrepancy notes are clearly labeled and excluded from the public design proposal. The approved site header/footer remain separately owned by the website task. Preview external buttons link to actual public reports, the existing reading guide and Product 865. The top historical action switches to the current fixture locally so the proposed journey can be reviewed.

The plugin edits are a smaller tested foundation, not a claim that this full layout is already implemented in WordPress. Preview does not install analytics, fetch live customer data, or submit forms. Font binaries and licenses are local; photo sources are documented in the audit.
