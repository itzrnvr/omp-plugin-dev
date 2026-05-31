# AGENTS.md — oh-my-pi Extension Development

## ⚠️ GOLDEN RULE: NEVER MODIFY CORE

**NEVER patch `node_modules` or modify oh-my-pi core files.** Everything can be done through the extension system. If you think you need to modify core, you haven't found the right extension API yet.

Core modifications will:
- Get overwritten on every `bun update` / `npm install`
- Break the user's installation
- Violate the extension architecture contract

---

## Extension Architecture

Extensions are TypeScript files that export a default factory function receiving `ExtensionAPI`:

```typescript
import type { ExtensionAPI } from "@oh-my-pi/pi-coding-agent";

export default function myExtension(pi: ExtensionAPI): void {
  pi.setLabel("My Extension");
  // Register tools, commands, hooks, etc.
}
```

### Installation Locations

| Scope | Path |
|-------|------|
| User-scoped | `~/.omp/agent/extensions/` |
| Project-scoped | `<project>/.omp/extensions/` |
| Via settings | `settings.json` → `extensions` array |

### Extension Discovery

Extensions are discovered by:
1. `package.json` with `omp.extensions` field pointing to entry file
2. Directory with `index.ts` (or `index.js`) as entry point
3. Single `.ts`/`.js` file in extensions directory

Register in `enabled.txt` (one quoted name per line):
```
"my-extension"
"another-extension"
```

---

## What's Possible via Extensions

### 1. Tools (LLM can call)

Register tools the LLM can invoke during conversation:

```typescript
pi.registerTool({
  name: "my_tool",
  label: "My Tool",
  description: "What this tool does (the LLM sees this)",
  parameters: {
    type: "object",
    properties: {
      input: { type: "string", description: "The input" }
    },
    required: ["input"]
  },
  async execute(id, params) {
    return { content: [{ type: "text", text: "result" }] };
  }
});
```

### 2. Slash Commands (User can type)

Register `/command` shortcuts:

```typescript
pi.registerCommand("mycommand", {
  description: "What this command does",
  handler: async (args: string, ctx) => {
    // ctx.ui.notify(), ctx.ui.prompt(), etc.
  }
});
```

### 3. System Prompt Sections

Inject instructions into the system prompt (every conversation turn):

```typescript
pi.registerSystemPromptSection(
  "section-id",
  "Instructions the LLM will see every turn."
);
```

**Use this to influence LLM behavior** — e.g., "always use `$...$` for math expressions."

### 4. Event Hooks

Intercept and modify behavior at key lifecycle points:

```typescript
// Modify tool output before display
pi.on("tool_result", async (event, ctx) => {
  // event.output.content = [{ type: "text", text: "..." }]
  // Return { output: event.output } to modify, or undefined to skip
});

// Intercept tool calls (can block execution)
pi.on("tool_call", async (event, ctx) => {
  // Return false to block the call
});

// System prompt events
pi.on("system_prompt", async (event, ctx) => {
  // Modify system prompt content
});
```

### 5. Assistant Message Interceptor

**This is the key API for modifying assistant message text before rendering.**

Use `setAssistantMessageEventInterceptor()` to intercept streaming events:

```typescript
pi.setAssistantMessageEventInterceptor(async (event) => {
  if (event.type === "text_end" && event.text) {
    // Modify event.text before it reaches the Markdown component
    return { ...event, text: modifiedText };
  }
  return event;
});
```

**Event types:**
- `text_start` — New text block starting
- `text_delta` — Streaming text chunk
- `text_end` — Text block complete (best place to modify)

**This replaces the need for core patches to the Markdown component.**

### 6. Custom Message Renderers

Register renderers for custom message types:

```typescript
pi.registerMessageRenderer("my-custom-type", (message, options, theme) => {
  // Return a Component or null to use default rendering
  return null;
});
```

### 7. UI Context

Access UI utilities in event handlers:

```typescript
pi.on("tool_result", async (event, ctx) => {
  // ctx.ui.notify("message", "info" | "warn" | "error")
  // ctx.ui.prompt("question")
  // ctx.ui.theme — current theme object
});
```

---

## Where to Look for Extension APIs

### Primary Sources

1. **DeepWiki** — Ask questions about oh-my-pi:
   ```
   mcp://deepwiki/ask_question?repoName=can1357/oh-my-pi
   ```
   - "How can extensions modify X?"
   - "What hooks are available for Y?"
   - "How to intercept Z?"

2. **Source Code** — `packages/coding-agent/src/extensibility/`
   - `ExtensionAPI` interface definition
   - Hook system implementation
   - Event types and handlers

3. **Existing Extensions** — Reference implementations:
   - `~/.omp/agent/extensions/cache-control/` — Simple tool registration
   - `~/.omp/agent/extensions/pty-session/` — Complex extension with PTY
   - `packages/swarm-extension/` — Multi-agent orchestration

### Key Files to Read

| File | What It Contains |
|------|------------------|
| `packages/coding-agent/src/extensibility/extension-api.ts` | ExtensionAPI interface |
| `packages/coding-agent/src/extensibility/hooks.ts` | Hook system |
| `packages/coding-agent/src/modes/components/assistant-message.ts` | How assistant messages render |
| `packages/tui/src/components/markdown.ts` | Markdown rendering (what you'd patch if you were wrong) |
| `packages/coding-agent/src/modes/theme/theme.ts` | Theme system |

### Investigation Pattern

When you think you need to modify core:

1. **Ask DeepWiki**: "How can an extension modify [X] without touching core?"
2. **Search source**: `grep -r "ExtensionAPI" packages/coding-agent/src/extensibility/`
3. **Check hooks**: Look for `pi.on()`, `setAssistantMessageEventInterceptor()`, `registerMessageRenderer()`
4. **Look at examples**: Read existing extensions in `~/.omp/agent/extensions/`

**If you still can't find it**, ask the user or file an issue — don't patch core.

---

## Common Patterns

### Pattern: Modify LLM Output Before Display

```typescript
pi.setAssistantMessageEventInterceptor(async (event) => {
  if (event.type === "text_end" && event.text) {
    event.text = transformMyWay(event.text);
    return event;
  }
  return event;
});
```

### Pattern: Inject LLM Behavior Instructions

```typescript
pi.registerSystemPromptSection(
  "my-rules",
  "When doing X, always do Y. Never do Z."
);
```

### Pattern: Post-Process Tool Output

```typescript
pi.on("tool_result", async (event, _ctx) => {
  if (!event.output?.content) return;
  
  for (const block of event.output.content) {
    if (block.type === "text") {
      block.text = transform(block.text);
    }
  }
  
  return { output: event.output };
});
```

### Pattern: Block Dangerous Tool Calls

```typescript
pi.on("tool_call", async (event, _ctx) => {
  if (event.tool === "bash" && event.params.command.includes("rm -rf /")) {
    return false; // Block execution
  }
});
```

---

## Debugging Extensions

### Check If Extension Loaded

Look for extension name in status bar or type `/extensions` (if available).

### View Logs

```bash
# Today's logs
cat ~/.omp/logs/$(date +%Y-%m-%d).log | grep -i "extension\|my-extension"

# Search for errors
grep -i "error\|failed" ~/.omp/logs/omp-*.log | tail -20
```

### Test Extension Loading

Add a console.log in your extension entry point:
```typescript
export default function myExtension(pi: ExtensionAPI): void {
  console.log("My extension loaded!");
  pi.setLabel("My Extension");
}
```

Check if it appears in logs.

---

## Anti-Patterns (What NOT to Do)

### ❌ Patching node_modules

```typescript
// WRONG — will get overwritten
const markdown = require("@oh-my-pi/pi-tui/src/components/markdown");
markdown.Markdown.prototype.render = function() { ... };
```

### ❌ Modifying core theme objects directly

```typescript
// WRONG — fragile, breaks on updates
const theme = require("@oh-my-pi/pi-coding-agent/src/modes/theme/theme");
theme.getMarkdownTheme().resolveLatexAscii = myConverter;
```

### ❌ Assuming you need core patches

If you think "the extension API doesn't support this," you're wrong. Look harder. Ask DeepWiki. Read the source. The API is designed to handle everything.

---

## Quick Reference

| Want To Do... | Use This API |
|---------------|--------------|
| Add a tool the LLM can call | `pi.registerTool()` |
| Add a `/command` for users | `pi.registerCommand()` |
| Inject instructions into LLM context | `pi.registerSystemPromptSection()` |
| Modify assistant message text | `setAssistantMessageEventInterceptor()` |
| Post-process tool output | `pi.on("tool_result")` |
| Block tool execution | `pi.on("tool_call")` |
| Custom message rendering | `pi.registerMessageRenderer()` |
| Access UI (notifications, prompts) | `ctx.ui.*` in event handlers |

---

## Example: Complete Extension Structure

```
my-extension/
├── index.ts           # Entry point (default export)
├── package.json       # With omp.extensions field
├── lib/
│   ├── converter.ts   # Utility functions
│   └── types.ts       # TypeScript types
└── test.ts            # Tests (run with bun)
```

### package.json

```json
{
  "type": "module",
  "name": "my-extension",
  "version": "1.0.0",
  "omp": {
    "extensions": ["./index.ts"]
  }
}
```

### index.ts

```typescript
import type { ExtensionAPI } from "@oh-my-pi/pi-coding-agent";
import { myConverter } from "./lib/converter";

export default function myExtension(pi: ExtensionAPI): void {
  pi.setLabel("My Extension");
  
  // 1. System prompt
  pi.registerSystemPromptSection("my-rules", "Always do X.");
  
  // 2. Assistant message interceptor
  pi.setAssistantMessageEventInterceptor(async (event) => {
    if (event.type === "text_end" && event.text) {
      return { ...event, text: myConverter(event.text) };
    }
    return event;
  });
  
  // 3. Tool for LLM
  pi.registerTool({
    name: "my_tool",
    description: "Does something useful",
    parameters: { type: "object", properties: {} },
    async execute() { return { content: [] }; }
  });
  
  // 4. Slash command
  pi.registerCommand("mycommand", {
    description: "Does something",
    handler: async (args, ctx) => { /* ... */ }
  });
  
  // 5. Tool result hook
  pi.on("tool_result", async (event, _ctx) => {
    // Post-process tool output
  });
}
```

---

## Remember

**The extension system is powerful enough to do anything.** If you can't figure out how, that's a documentation/research problem, not an architecture problem. Never modify core.

When in doubt:
1. Ask DeepWiki
2. Read existing extensions
3. Read the ExtensionAPI source
4. Ask the user

**Never:**
1. Patch node_modules
2. Modify core theme objects
3. Assume you need core changes
