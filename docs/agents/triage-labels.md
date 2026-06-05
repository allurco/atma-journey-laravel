# Triage Labels

The skills speak in terms of five canonical triage roles. On this repo's Trello board
(`69dfaed65817fe1c0f67f674`) they are dedicated **triage labels**, created alongside the board's
existing **domain** labels (Financeiro, Agenda, Prontuario…) and **priority** labels (Alta/Média).
A card carries exactly one triage label at a time; its **domain** label and **list** (workflow column)
are tracked independently.

| Role in mattpocock/skills | Trello label      | Color  | Label ID (snapshot)        | Meaning                                  |
| ------------------------- | ----------------- | ------ | -------------------------- | ---------------------------------------- |
| `needs-triage`            | `needs-triage`    | pink   | `6a22fbb105462d7fb837c10e` | Maintainer needs to evaluate this issue  |
| `needs-info`              | `needs-info`      | sky    | `6a22fbb1f6d3b401a65b1920` | Waiting on reporter for more information |
| `ready-for-agent`         | `ready-for-agent` | lime   | `6a22fbb1c9f5556b5492a15d` | Fully specified, ready for an AFK agent  |
| `ready-for-human`         | `ready-for-human` | purple | `6a22fbb2b0624e1ee6104e67` | Requires human implementation            |
| `wontfix`                 | `wontfix`         | black  | `6a22fbb2b6ffc93a777bcc3a` | Will not be actioned                     |

When a skill mentions a role (e.g. "apply the AFK-ready triage label"), apply the corresponding Trello
label. New cards default to `needs-triage`. IDs are a snapshot — resolve by name if a lookup fails.
