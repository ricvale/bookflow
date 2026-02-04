# Agent Skills

This directory contains **AI-agent-friendly documentation** following the [agentskills.io](https://agentskills.io) standard.

## What are Agent Skills?

Agent Skills are structured documentation that helps AI coding assistants understand:
- **What** a feature does
- **When** to use it
- **How** to implement it correctly
- **What** to avoid

## Structure

Each skill is a directory containing:
- `SKILL.md` (required): Main documentation with YAML frontmatter
- `references/` (optional): Detailed specs, examples, templates
- `scripts/` (optional): Helper scripts for complex operations

## Available Skills

### Core Skills
- **[booking](./booking/SKILL.md)**: Booking creation and conflict resolution
- **[tenancy](./tenancy/SKILL.md)**: Multi-tenant isolation and security
- **[authentication](./authentication/SKILL.md)**: JWT-based auth and session management
- **[availability](./availability/SKILL.md)**: Resource scheduling and time zone handling
- **[calendar-integration](./calendar-integration/SKILL.md)**: Google Calendar sync and webhooks

## SKILL.md Format

```markdown
---
name: skill-name
description: Brief description for AI to understand when to use this skill
---

# Skill Name

## Responsibilities
- What this skill handles

## Constraints
- Rules that must be followed

## Edge Cases
- Tricky scenarios to watch out for

## Non-goals
- What this skill does NOT handle

## Architecture Notes
- Layer-specific implementation guidance
```

## Best Practices

### For AI Agents
1. **Read the description first**: Determine if this skill is relevant
2. **Follow constraints strictly**: These are architectural rules
3. **Check edge cases**: Before implementing, review known pitfalls
4. **Respect non-goals**: Don't add features outside the skill's scope

### For Humans
1. **Keep skills focused**: One domain concept per skill
2. **Be explicit**: AI agents need clear, unambiguous instructions
3. **Provide examples**: Show correct and incorrect patterns
4. **Link to code**: Reference actual implementation files

## Progressive Disclosure

Skills use **progressive disclosure**:
1. AI reads `name` and `description` (lightweight)
2. If relevant, AI loads full `SKILL.md`
3. If needed, AI reads referenced files

This keeps context windows manageable.

## Adding New Skills

```bash
# Create new skill directory
mkdir skills/new-skill

# Create SKILL.md with frontmatter
cat > skills/new-skill/SKILL.md << 'EOF'
---
name: new-skill
description: What this skill does and when to use it
---

# New Skill

[Your documentation here]
EOF
```

## References
- [agentskills.io](https://agentskills.io)
- [Agent Skills Specification](https://agentskills.io/spec)
