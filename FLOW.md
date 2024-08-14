# Configuration Flow Visualization

<!-- https://docs.github.com/en/get-started/writing-on-github/working-with-advanced-formatting/creating-diagrams -->

<!-- TODO: Need to correct flow after last changes in script -->

```mermaid
graph TD
    A[Check if config.json file exists] --> B[Read config.json file]
    B --> C{Is something missing?}
    C -->|No| M[Generate images]
    C -->|Yes| D[Check environment variables]
    D --> E{Is something missing?}
    E -->|No| M
    E -->|Yes| F[Check arguments passed to script]
    F --> G{Is something missing?}
    G -->|No| M
    G -->|Yes| H[Check query parameters in URL]
    H --> I{Is something still missing?}
    I -->|No| M
    I -->|Yes| L[Exit with error message]
```
