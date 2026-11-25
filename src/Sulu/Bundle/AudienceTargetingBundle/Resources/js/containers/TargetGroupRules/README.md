The `TargetGroupRules` container is the field type to edit the rules of a target group. It shows a table of the rules
with their title and frequency, buttons to add, edit and delete rules, and a `RuleOverlay` to edit a rule. A rule
consists of a title, a frequency and a list of conditions, whose types are registered in the `ruleRegistry`
(`registries/ruleRegistry`), so it cannot be rendered without that registration in this documentation.

| Prop       | Description                                                                              |
|------------|------------------------------------------------------------------------------------------|
| `value`    | Array of rules `{title, frequency, conditions: Array<{type, condition}>}`.               |
| `onChange` | Called with the new array of rules.                                                      |

```jsx static
<TargetGroupRules
    onChange={this.handleChange}
    value={[
        {
            title: 'Visitors from Germany',
            frequency: 1,
            conditions: [{type: 'locale', condition: {locale: 'de'}}],
        },
    ]}
/>
```
