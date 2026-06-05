# Issue tracker: Trello (via MCP)

Implementation issues for this repo are **Trello cards** on the **atma-journey** board, accessed
through the globally-registered **`trello`** MCP server (`atlassian-trello-mcp`).
PRD specs stay as markdown under `docs/prds/`; a card references its PRD (e.g. "PRD-3 Pipeline CRM").

## Board & auth
- Board ID: `69dfaed65817fe1c0f67f674` (board name: *atma-journey*)
- The MCP server reads `TRELLO_API_KEY` and `TRELLO_TOKEN` from the environment. On this machine they
  live in the **macOS Keychain** and are exported by `~/.zshrc`:
  `export TRELLO_API_KEY=$(security find-generic-password -a "$USER" -s TRELLO_API_KEY -w)` (same for token).
  → Launch `claude` from a terminal so the MCP subprocess inherits them. If a tool reports
  `apiKey/token Required`, the subprocess didn't inherit the env — relaunch from a terminal, or pass
  `apiKey`/`token` explicitly to the tool.
- Re-add the server if missing: `claude mcp add --scope user trello -- npx -y atlassian-trello-mcp`

## Board model — three axes

**1. Lists (columns) = workflow.** New issues are created in **Backlog Geral**.

| List | Workflow meaning | List ID (snapshot) |
|---|---|---|
| Backlog Geral | intake / triage backlog | `69dfaef5327e415d78e6b637` |
| Prioridade Alta / MVP | prioritized, ready to build | `69ef907d2a2d1249a73894bd` |
| Em Desenvolvimento | in progress | `69ef90889a4a8a3617dbe216` |
| Em Teste | in testing / review | `69ef9090944463c63019b64d` |
| Ajustes | rework / adjustments | `69ef909836e8bf3039072116` |
| Concluído | done | `69ef909deb5e1d4fc8902879` |

**2. Domain labels = which PRD / bounded context.** Apply exactly one.

| Label | PRD | Label ID (snapshot) |
|---|---|---|
| Fundação/Tenancy | PRD-0 Platform Foundation | `6a22fbb4fa01440c257ce121` |
| Configurações | PRD-1 Settings & Identity | `69dfaed65817fe1c0f67f68f` |
| Pacientes | PRD-2 Patients | `6a22fbb3ffd5ce4f5d10b1b3` |
| Pipeline/CRM | PRD-3 Pipeline CRM | `6a22fbb37be8a021379ffb40` |
| Agenda | PRD-4 Scheduling | `69ef90f94b9e12e69c0691ee` |
| Financeiro | PRD-5 Financial | `69ef9a659cce7a9c223d4e45` |
| Prontuario | PRD-6 Clinical / EHR | `69dfaed65817fe1c0f67f692` |
| Relatorios | PRD-9 Dashboard & Analytics | `69ef9b315dbe6a11c12cbdd2` |
| UX/UI | cross-cutting frontend | `69dfaed65817fe1c0f67f690` |
| Segurança | cross-cutting security / auth | `69ef97f2993590e132b9bba4` |
| Inovação | Phase 2 (AI / messaging) | `69dfaed65817fe1c0f67f68e` |
| Estoque | Phase 2 (inventory) | `69ef99cebce04a7efc28b773` |

> Gap: **PRD-7 Communication** and **PRD-8 Lead Ingestion** have no dedicated label yet — create one
> (`Comunicação`, `Leads`) when those cards are filed, or reuse the closest existing label.

**3. Priority + triage labels.** Optionally one priority; exactly one triage state.

- Priority: `Alta prioridade` (`69dfaed65817fe1c0f67f691`), `Média Prioridade` (`69dfaed65817fe1c0f67f693`).
- Triage state: see `triage-labels.md` (default on a new card: `needs-triage`).

A typical card: list `Backlog Geral` + labels `[Financeiro, Alta prioridade, ready-for-agent]`.

## When a skill says "publish to the issue tracker"
Use the `trello` MCP `create_card` tool: target the **Backlog Geral** list, `name` = title,
`desc` = full markdown body (context, acceptance criteria, PRD reference). Then apply labels:
the **domain** label for the PRD + the **triage** label (`needs-triage` unless otherwise specified)
via `trello_add_label_to_card`.

## When a skill says "fetch the relevant ticket"
The user passes a card URL or short ID. Use the `trello` MCP `get_card` tool.

## When triage or progress changes
- **Triage state** → swap the triage label (`trello_remove_label_from_card` + `trello_add_label_to_card`).
- **Build progress** → `move_card` to another list.

## Fallback if the MCP server is unavailable
Use the Trello REST API directly with the same credentials (resolve list/label by name):
- `curl -s "https://api.trello.com/1/boards/69dfaed65817fe1c0f67f674/lists?key=$TRELLO_API_KEY&token=$TRELLO_TOKEN"`
- `curl -s -X POST "https://api.trello.com/1/cards" -d "idList=<id>" --data-urlencode "name=<t>" --data-urlencode "desc=<b>" -d "idLabels=<a>,<b>" -d "key=$TRELLO_API_KEY" -d "token=$TRELLO_TOKEN"`
