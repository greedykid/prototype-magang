# Design Language: The AI Customer Support Platform for Every Business - Crisp

> Extracted from `https://crisp.chat` on May 28, 2026
> 1273 elements analyzed

This document describes the complete design language of the website. It is structured for AI/LLM consumption — use it to faithfully recreate the visual design in any framework.

## Color Palette

### Primary Colors

| Role | Hex | RGB | HSL | Usage Count |
|------|-----|-----|-----|-------------|
| Primary | `#1972f5` | rgb(25, 114, 245) | hsl(216, 92%, 53%) | 177 |
| Secondary | `#030d26` | rgb(3, 13, 38) | hsl(223, 85%, 8%) | 229 |
| Accent | `#69abf8` | rgb(105, 171, 248) | hsl(212, 91%, 69%) | 1 |

### Neutral Colors

| Hex | HSL | Usage Count |
|-----|-----|-------------|
| `#000000` | hsl(0, 0%, 0%) | 477 |
| `#ffffff` | hsl(0, 0%, 100%) | 167 |
| `#828ea1` | hsl(217, 14%, 57%) | 48 |
| `#5d6b98` | hsl(226, 24%, 48%) | 47 |
| `#7d89b0` | hsl(226, 24%, 59%) | 21 |
| `#dcdfea` | hsl(227, 25%, 89%) | 20 |
| `#344055` | hsl(218, 24%, 27%) | 19 |
| `#667085` | hsl(221, 13%, 46%) | 16 |
| `#404968` | hsl(227, 24%, 33%) | 16 |

### Background Colors

Used on large-area elements: `#030d26`, `#fcfcfd`, `#ffffff`, `#71b9ed`, `#1972f5`, `#f8f8fc`, `#242f47`, `#69abf8`, `#050c23`

### Text Colors

Text color palette: `#000000`, `#242f47`, `#0000ee`, `#ffffff`, `#7d89b0`, `#1972f5`, `#667085`, `#5d6b98`, `#030d26`, `#344055`

### Gradients

```css
background-image: linear-gradient(rgba(255, 255, 255, 0.24), rgba(255, 255, 255, 0)), none;
```

```css
background-image: linear-gradient(rgba(0, 0, 0, 0.02), rgba(0, 0, 0, 0.04));
```

```css
background-image: linear-gradient(rgb(25, 114, 245), rgb(4, 86, 206));
```

```css
background-image: linear-gradient(rgb(204, 242, 199), rgb(157, 225, 149));
```

```css
background-image: linear-gradient(rgb(237, 226, 253), rgb(213, 192, 249));
```

```css
background-image: linear-gradient(rgb(226, 235, 255), rgb(192, 211, 253));
```

```css
background-image: linear-gradient(rgba(72, 85, 117, 0.8), rgba(3, 13, 38, 0.8));
```

```css
background-image: linear-gradient(rgba(255, 225, 68, 0.1), rgba(255, 145, 0, 0.1));
```

```css
background-image: linear-gradient(rgb(240, 231, 254), rgb(220, 202, 252));
```

```css
background-image: linear-gradient(rgb(227, 236, 253), rgb(194, 212, 250));
```

```css
background-image: linear-gradient(rgb(233, 238, 244), rgb(206, 216, 230));
```

```css
background-image: linear-gradient(rgb(230, 250, 227), rgb(200, 242, 195));
```

```css
background-image: linear-gradient(rgb(255, 237, 223), rgb(254, 215, 187));
```

```css
background-image: linear-gradient(rgb(254, 227, 231), rgb(252, 195, 203));
```

```css
background-image: linear-gradient(rgb(78, 156, 255), rgb(25, 114, 245));
```

```css
background-image: linear-gradient(rgb(104, 169, 247), rgb(55, 112, 236));
```

```css
background-image: linear-gradient(rgb(133, 142, 159), rgb(78, 85, 102));
```

```css
background-image: linear-gradient(rgb(131, 225, 146), rgb(76, 192, 89));
```

```css
background-image: linear-gradient(rgb(108, 186, 252), rgb(58, 132, 247));
```

```css
background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' width='320' height='16' fill='none' viewBox='0 0 320 16'%3E%3ClinearGradient id='a'%3E%3Cstop offset='0' stop-color='%23b9c0d4'/%3E%3Cstop offset='1' stop-color='%23b9c0d4' stop-opacity='0'/%3E%3C/linearGradient%3E%3ClinearGradient xlink:href='%23a' id='b' x1='138' x2='0' y1='9.25' y2='9.25' gradientUnits='userSpaceOnUse'/%3E%3ClinearGradient xlink:href='%23a' id='c' x1='182' x2='320' y1='9.25' y2='9.25' gradientUnits='userSpaceOnUse'/%3E%3Cpath stroke='url(%23b)' d='M138 8.25H0'/%3E%3Cg stroke='%23b9c0d4'%3E%3Cpath stroke-linecap='round' d='M138.25 1v7.25m0 0v7.25m0-7.25h7.25m-7.25 0H131'/%3E%3Ccircle cx='160' cy='8.25' r='5.5' transform='rotate(-90 160 8.25)'/%3E%3Cpath stroke-linecap='round' d='M181.75 1v7.25m0 0v7.25m0-7.25h-7.25m7.25 0H189'/%3E%3C/g%3E%3Cpath stroke='url(%23c)' d='M182 8.25h138'/%3E%3C/svg%3E");
```

```css
background-image: linear-gradient(rgba(255, 255, 255, 0.16), rgba(255, 255, 255, 0)), none;
```

### Full Color Inventory

| Hex | Contexts | Count |
|-----|----------|-------|
| `#242f47` | text, border, background | 886 |
| `#000000` | text, border, background | 477 |
| `#0000ee` | text, border | 286 |
| `#030d26` | background, text, border | 229 |
| `#1972f5` | background, border, text | 177 |
| `#ffffff` | text, border, background | 167 |
| `#828ea1` | text, border | 48 |
| `#5d6b98` | text, border | 47 |
| `#9d4b0f` | text, border | 43 |
| `#eaecf5` | border | 23 |
| `#7d89b0` | text, border | 21 |
| `#dcdfea` | border, background | 20 |
| `#5a28b0` | text, border | 20 |
| `#344055` | text, border | 19 |
| `#3d7fde` | border | 18 |
| `#667085` | text, border | 16 |
| `#404968` | text, border | 16 |
| `#a14300` | text, border | 14 |
| `#156f08` | text, border | 12 |
| `#003dc3` | text, border | 12 |
| `#bc081f` | text, border | 12 |
| `#19253b` | text, border | 10 |
| `#095ad2` | text, border | 8 |
| `#15254c` | text, border | 8 |
| `#fdb022` | text, border | 8 |
| `#955cf4` | border, text | 7 |
| `#003399` | text, border | 2 |
| `#cbdbf4` | border | 1 |
| `#71b9ed` | background | 1 |
| `#69abf8` | background | 1 |

## Typography

### Font Families

- **Crisp Aeonik Pro Regular** — used for all (848 elements)
- **Times** — used for body (200 elements)
- **Crisp Aeonik Pro Bold** — used for all (107 elements)
- **Crisp Aeonik Pro Medium** — used for all (104 elements)
- **Arial** — used for body (8 elements)
- **Crisp Inter Bold** — used for all (6 elements)

### Type Scale

| Size (px) | Size (rem) | Weight | Line Height | Letter Spacing | Used On |
|-----------|------------|--------|-------------|----------------|---------|
| 48px | 3rem | 400 | 66px | normal | h1, span, br |
| 46px | 2.875rem | 400 | 56px | -1px | h2 |
| 40px | 2.5rem | 400 | 46px | -1px | h2, span |
| 38px | 2.375rem | 400 | 46px | -0.2px | h3, span, br |
| 32px | 2rem | 400 | 44px | normal | p, h3 |
| 26px | 1.625rem | 400 | 34px | normal | h3 |
| 21px | 1.3125rem | 400 | 28px | normal | p |
| 20px | 1.25rem | 400 | 24px | 0.1px | span, p |
| 19px | 1.1875rem | 400 | normal | normal | p |
| 18px | 1.125rem | 400 | normal | normal | div, img, span, p |
| 17px | 1.0625rem | 400 | 21px | normal | span, h3, p |
| 16px | 1rem | 400 | normal | normal | html, head, meta, title |
| 15px | 0.9375rem | 400 | 18px | 0.1px | span, div, p, a |
| 14.5px | 0.9063rem | 400 | 19px | normal | p, span, div, a |
| 14px | 0.875rem | 400 | normal | normal | ul, li, span, svg |

### Heading Scale

```css
h1 { font-size: 48px; font-weight: 400; line-height: 66px; }
h2 { font-size: 46px; font-weight: 400; line-height: 56px; }
h2 { font-size: 40px; font-weight: 400; line-height: 46px; }
h3 { font-size: 38px; font-weight: 400; line-height: 46px; }
h3 { font-size: 32px; font-weight: 400; line-height: 44px; }
h3 { font-size: 26px; font-weight: 400; line-height: 34px; }
h3 { font-size: 17px; font-weight: 400; line-height: 21px; }
```

### Body Text

```css
body { font-size: 16px; font-weight: 400; line-height: normal; }
```

### Font Weights in Use

`400` (1273x)

## Spacing

**Base unit:** 2px

| Token | Value | Rem |
|-------|-------|-----|
| spacing-1 | 1px | 0.0625rem |
| spacing-35 | 35px | 2.1875rem |
| spacing-40 | 40px | 2.5rem |
| spacing-50 | 50px | 3.125rem |
| spacing-58 | 58px | 3.625rem |
| spacing-80 | 80px | 5rem |
| spacing-100 | 100px | 6.25rem |
| spacing-110 | 110px | 6.875rem |
| spacing-120 | 120px | 7.5rem |
| spacing-150 | 150px | 9.375rem |
| spacing-160 | 160px | 10rem |

## Border Radii

| Label | Value | Count |
|-------|-------|-------|
| md | 6px | 1 |
| md | 10px | 32 |
| lg | 14px | 5 |
| xl | 18px | 2 |
| xl | 24px | 10 |
| full | 28px | 6 |
| full | 32px | 5 |
| full | 40px | 6 |
| full | 46px | 4 |
| full | 50px | 11 |
| full | 100px | 6 |

## Box Shadows

**sm (inset)** — blur: 0px
```css
box-shadow: rgba(255, 255, 255, 0.2) 0px 0px 0px 1px inset;
```

**sm** — blur: 0px
```css
box-shadow: rgb(217, 219, 230) 0px 0px 0px 1px;
```

**sm** — blur: 0px
```css
box-shadow: rgba(25, 114, 245, 0.14) 0px 0px 0px 2px, rgb(252, 252, 253) 0px 0px 0px 1px;
```

**sm** — blur: 0px
```css
box-shadow: rgba(0, 0, 0, 0.18) 0px 0px 0px 2px;
```

**sm (inset)** — blur: 0px
```css
box-shadow: rgb(255, 255, 255) 0px 0px 0px 2px inset;
```

**sm** — blur: 0px
```css
box-shadow: rgba(255, 255, 255, 0.6) 0px 0px 0px 8px, rgba(0, 0, 0, 0.08) 0px 2px 8px 0px, rgba(0, 0, 0, 0.1) 0px 8px 40px 0px;
```

**sm** — blur: 0px
```css
box-shadow: rgb(234, 236, 245) 0px 0px 0px 1px;
```

**sm** — blur: 0px
```css
box-shadow: rgba(25, 114, 245, 0.08) 0px 0px 0px 4px, rgba(0, 0, 0, 0.03) 0px 3px 2px 0px;
```

**sm (inset)** — blur: 0px
```css
box-shadow: rgb(3, 13, 38) 0px 0px 0px 1px inset;
```

**xs** — blur: 0px
```css
box-shadow: rgba(0, 0, 0, 0.08) 0px 2px 0px 0px;
```

**xs** — blur: 0px
```css
box-shadow: rgba(0, 0, 0, 0.07) 0px 2.5px 0px 0px;
```

**xs** — blur: 2px
```css
box-shadow: rgba(0, 0, 0, 0.03) 0px 1px 2px 0px;
```

**xs** — blur: 2px
```css
box-shadow: rgba(42, 59, 81, 0.12) 0px 1px 2px 0px;
```

**xs** — blur: 2px
```css
box-shadow: rgba(16, 24, 40, 0.05) 0px 1px 2px 0px;
```

**sm** — blur: 2px
```css
box-shadow: rgba(0, 0, 0, 0.08) 0px 2px 2px 0px;
```

**sm** — blur: 2px
```css
box-shadow: rgba(0, 0, 0, 0.01) 0px 2px 2px 0px;
```

**md** — blur: 6px
```css
box-shadow: rgba(16, 24, 40, 0.05) 0px 3px 6px 0px;
```

**md** — blur: 3.5px
```css
box-shadow: rgba(17, 67, 139, 0.03) 0px 9.7px 3.5px 0px, rgba(252, 252, 253, 0.05) 0px 0px 0px 1px, rgba(17, 67, 139, 0.08) 0px 2.6px 2.6px 0px, rgba(17, 67, 139, 0.05) 0px 5.2px 2.6px 0px, rgba(17, 67, 139, 0.06) 0px 28px 21px -10px;
```

## CSS Custom Properties

### Semantic

```css
success: [object Object];
warning: [object Object];
error: [object Object];
info: [object Object];
```

## Breakpoints

| Name | Value | Type |
|------|-------|------|
| 400px | 400px | max-width |
| sm | 440px | max-width |
| sm | 500px | max-width |
| sm | 540px | max-width |
| sm | 640px | max-width |
| md | 750px | max-width |
| 880px | 880px | max-width |
| lg | 1024px | max-width |
| lg | 1080px | max-width |
| xl | 1280px | max-width |
| xl | 1320px | max-width |
| 1360px | 1360px | max-width |
| 2xl | 1500px | max-width |

## Transitions & Animations

**Easing functions:** `[object Object]`, `[object Object]`, `[object Object]`

**Durations:** `0.2s`, `0.15s`, `0.1s`, `0.4s`, `0.25s`, `1.2s`, `0.9s`

### Common Transitions

```css
transition: all;
transition: top 0.2s ease-in-out;
transition: background 0.15s ease-in-out, border 0.15s ease-in-out, color 0.15s ease-in-out;
transition: border-color 0.1s linear, transform 0.1s linear;
transition: 0.2s;
transition: background-color 0.2s linear;
transition: transform 0.1s ease-in-out;
transition: opacity 0.2s, transform 0.2s;
transition: color 0.2s;
transition: background-color 0.1s linear, color 0.1s linear;
```

### Keyframe Animations

**fadeInUp**
```css
@keyframes fadeInUp {
  0% { opacity: 0; transform: translate3d(0px, 25px, 0px); }
  100% { opacity: 1; transform: translateZ(0px); }
}
```

**fadeOutUp**
```css
@keyframes fadeOutUp {
  0% { opacity: 1; transform: translateZ(0px); }
  100% { opacity: 0; transform: translate3d(0px, 25px, 0px); }
}
```

**fadeInLeft**
```css
@keyframes fadeInLeft {
  0% { opacity: 0; transform: translate3d(-25px, 0px, 0px); }
  100% { opacity: 1; transform: none; }
}
```

**fadeOutLeft**
```css
@keyframes fadeOutLeft {
  0% { opacity: 1; transform: translateZ(0px); }
  100% { opacity: 0; transform: translate3d(-25px, 0px, 0px); }
}
```

**fadeInRight**
```css
@keyframes fadeInRight {
  0% { opacity: 0; transform: translate3d(25px, 0px, 0px); }
  100% { opacity: 1; transform: none; }
}
```

**fadeOutRight**
```css
@keyframes fadeOutRight {
  0% { opacity: 1; transform: translateZ(0px); }
  100% { opacity: 0; transform: translate3d(25px, 0px, 0px); }
}
```

**fadeInDown**
```css
@keyframes fadeInDown {
  0% { opacity: 0; transform: translate3d(0px, -25px, 0px); }
  100% { opacity: 1; transform: translateZ(0px); }
}
```

**fadeOutDown**
```css
@keyframes fadeOutDown {
  0% { opacity: 1; transform: translateZ(0px); }
  100% { opacity: 0; transform: translate3d(0px, -25px, 0px); }
}
```

**fadeInUpSmall**
```css
@keyframes fadeInUpSmall {
  0% { opacity: 0; transform: translate3d(0px, 6px, 0px); }
  100% { opacity: 1; transform: none; }
}
```

**fadeInDownSmall**
```css
@keyframes fadeInDownSmall {
  0% { opacity: 0; transform: translate3d(0px, -6px, 0px); }
  100% { opacity: 1; transform: none; }
}
```

## Component Patterns

Detected UI component patterns and their most common styles:

### Buttons (21 instances)

```css
.button {
  background-color: rgb(25, 114, 245);
  color: rgb(255, 255, 255);
  font-size: 16px;
  font-weight: 400;
  padding-top: 6px;
  padding-right: 20px;
  border-radius: 12px;
}
```

### Cards (143 instances)

```css
.card {
  background-color: rgb(255, 255, 255);
  border-radius: 0px;
  box-shadow: rgb(255, 255, 255) 0px 0px 0px 2px inset;
  padding-top: 0px;
  padding-right: 0px;
}
```

### Links (117 instances)

```css
.link {
  color: rgb(0, 0, 238);
  font-size: 16px;
  font-weight: 400;
}
```

### Navigation (117 instances)

```css
.navigatio {
  background-color: rgb(25, 114, 245);
  color: rgb(36, 47, 71);
  padding-top: 0px;
  padding-bottom: 0px;
  padding-left: 0px;
  padding-right: 0px;
  position: static;
  box-shadow: rgba(255, 255, 255, 0.2) 0px 0px 0px 1px inset;
}
```

### Footer (105 instances)

```css
.foote {
  background-color: rgb(252, 252, 253);
  color: rgb(3, 13, 38);
  padding-top: 0px;
  padding-bottom: 0px;
  font-size: 16px;
}
```

### Modals (8 instances)

```css
.modal {
  border-radius: 0px;
  padding-top: 0px;
  padding-right: 0px;
}
```

### Dropdowns (96 instances)

```css
.dropdown {
  border-radius: 0px;
  border-color: rgb(36, 47, 71);
  padding-top: 0px;
}
```

### Badges (44 instances)

```css
.badge {
  color: rgb(255, 255, 255);
  font-size: 16px;
  font-weight: 400;
  padding-top: 0px;
  padding-right: 0px;
  border-radius: 0px;
}
```

### Accordions (4 instances)

```css
.accordion {
  color: rgb(0, 0, 238);
  font-size: 16px;
  padding-top: 0px;
  padding-right: 0px;
  border-color: rgb(0, 0, 238);
}
```

### Tooltips (32 instances)

```css
.tooltip {
  background-color: rgba(255, 255, 255, 0.9);
  color: rgb(36, 47, 71);
  font-size: 13px;
  border-radius: 0px;
  padding-top: 0px;
  padding-right: 0px;
  box-shadow: rgba(16, 24, 40, 0.05) 0px 3px 6px 0px;
}
```

### ProgressBars (20 instances)

```css
.progressBar {
  background-color: rgb(220, 223, 234);
  color: rgb(0, 0, 238);
  border-radius: 0px;
  font-size: 16px;
}
```

### Switches (18 instances)

```css
.switche {
  background-color: rgb(252, 252, 253);
  border-radius: 0px;
  border-color: rgb(36, 47, 71);
}
```

## Component Clusters

Reusable component instances grouped by DOM structure and style similarity:

### Button — 1 instance, 1 variant

**Variant 1** (1 instance)

```css
  background: rgb(25, 114, 245);
  color: rgb(255, 255, 255);
  padding: 4px 8px 4px 8px;
  border-radius: 8px;
  border: 1px solid rgb(61, 127, 222);
  font-size: 16px;
  font-weight: 400;
```

### Button — 18 instances, 2 variants

**Variant 1** (16 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(255, 255, 255);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(255, 255, 255);
  font-size: 12px;
  font-weight: 400;
```

**Variant 2** (2 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(3, 13, 38);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(3, 13, 38);
  font-size: 14px;
  font-weight: 400;
```

### Button — 4 instances, 3 variants

**Variant 1** (1 instance)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(3, 13, 38);
  padding: 6px 18px 6px 18px;
  border-radius: 12px;
  border: 0px none rgb(3, 13, 38);
  font-size: 16px;
  font-weight: 400;
```

**Variant 2** (2 instances)

```css
  background: rgb(3, 13, 38);
  color: rgb(255, 255, 255);
  padding: 10px 20px 10px 20px;
  border-radius: 14px;
  border: 1px solid rgb(3, 13, 38);
  font-size: 16px;
  font-weight: 400;
```

**Variant 3** (1 instance)

```css
  background: rgb(255, 255, 255);
  color: rgb(52, 64, 84);
  padding: 6px 18px 6px 18px;
  border-radius: 12px;
  border: 1px solid rgba(18, 55, 105, 0.08);
  font-size: 16px;
  font-weight: 400;
```

### Button — 11 instances, 1 variant

**Variant 1** (11 instances)

```css
  background: rgb(25, 114, 245);
  color: rgb(255, 255, 255);
  padding: 6px 18px 6px 18px;
  border-radius: 12px;
  border: 1px solid rgb(61, 127, 222);
  font-size: 16px;
  font-weight: 400;
```

### Button — 13 instances, 1 variant

**Variant 1** (13 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(255, 255, 255);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(255, 255, 255);
  font-size: 16px;
  font-weight: 400;
```

### Button — 1 instance, 1 variant

**Variant 1** (1 instance)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(36, 47, 71);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(36, 47, 71);
  font-size: 16px;
  font-weight: 400;
```

### Button — 1 instance, 1 variant

**Variant 1** (1 instance)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(0, 0, 0);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(0, 0, 0);
  font-size: 13.3333px;
  font-weight: 400;
```

### Card — 11 instances, 2 variants

**Variant 1** (1 instance)

```css
  background: rgb(252, 252, 253);
  color: rgb(36, 47, 71);
  padding: 24px 2px 32px 2px;
  border-radius: 24px;
  border: 1px solid rgb(234, 236, 245);
  font-size: 16px;
  font-weight: 400;
```

**Variant 2** (10 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(36, 47, 71);
  padding: 24px 32px 24px 32px;
  border-radius: 24px;
  border: 0px none rgb(36, 47, 71);
  font-size: 16px;
  font-weight: 400;
```

### Button — 4 instances, 1 variant

**Variant 1** (4 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(36, 47, 71);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(36, 47, 71);
  font-size: 16px;
  font-weight: 400;
```

### Card — 18 instances, 1 variant

**Variant 1** (18 instances)

```css
  background: rgb(255, 255, 255);
  color: rgb(36, 47, 71);
  padding: 2px 2px 2px 2px;
  border-radius: 28px;
  border: 1px solid rgb(234, 236, 245);
  font-size: 16px;
  font-weight: 400;
```

### Card — 1 instance, 1 variant

**Variant 1** (1 instance)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(36, 47, 71);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(36, 47, 71);
  font-size: 16px;
  font-weight: 400;
```

### Card — 4 instances, 1 variant

**Variant 1** (4 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(36, 47, 71);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(36, 47, 71);
  font-size: 16px;
  font-weight: 400;
```

### Card — 4 instances, 1 variant

**Variant 1** (4 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(36, 47, 71);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(36, 47, 71);
  font-size: 16px;
  font-weight: 400;
```

### Card — 27 instances, 1 variant

**Variant 1** (27 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(36, 47, 71);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(36, 47, 71);
  font-size: 16px;
  font-weight: 400;
```

### Card — 11 instances, 1 variant

**Variant 1** (11 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(25, 114, 245);
  padding: 0px 0px 0px 8px;
  border-radius: 0px;
  border: 0px none rgb(25, 114, 245);
  font-size: 16px;
  font-weight: 400;
```

### Card — 4 instances, 1 variant

**Variant 1** (4 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(3, 13, 38);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(3, 13, 38);
  font-size: 26px;
  font-weight: 400;
```

### Card — 1 instance, 1 variant

**Variant 1** (1 instance)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(36, 47, 71);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(36, 47, 71);
  font-size: 16px;
  font-weight: 400;
```

### Card — 3 instances, 1 variant

**Variant 1** (3 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(36, 47, 71);
  padding: 16px 0px 16px 0px;
  border-radius: 0px;
  border: 0px none rgb(36, 47, 71);
  font-size: 16px;
  font-weight: 400;
```

### Card — 3 instances, 1 variant

**Variant 1** (3 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(36, 47, 71);
  padding: 0px 0px 0px 0px;
  border-radius: 12px;
  border: 0px none rgb(36, 47, 71);
  font-size: 16px;
  font-weight: 400;
```

### Card — 3 instances, 1 variant

**Variant 1** (3 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(36, 47, 71);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(36, 47, 71);
  font-size: 16px;
  font-weight: 400;
```

### Card — 3 instances, 1 variant

**Variant 1** (3 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(36, 47, 71);
  padding: 0px 0px 0px 0px;
  border-radius: 12px;
  border: 0px none rgb(36, 47, 71);
  font-size: 16px;
  font-weight: 400;
```

### Card — 3 instances, 1 variant

**Variant 1** (3 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(36, 47, 71);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(36, 47, 71);
  font-size: 16px;
  font-weight: 400;
```

### Card — 3 instances, 1 variant

**Variant 1** (3 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(255, 255, 255);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(255, 255, 255);
  font-size: 14px;
  font-weight: 400;
```

### Card — 2 instances, 1 variant

**Variant 1** (2 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(36, 47, 71);
  padding: 24px 24px 24px 24px;
  border-radius: 0px;
  border: 0px none rgb(36, 47, 71);
  font-size: 16px;
  font-weight: 400;
```

### Card — 5 instances, 1 variant

**Variant 1** (5 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(36, 47, 71);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(36, 47, 71);
  font-size: 16px;
  font-weight: 400;
```

### Card — 2 instances, 1 variant

**Variant 1** (2 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(36, 47, 71);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(36, 47, 71);
  font-size: 16px;
  font-weight: 400;
```

### Card — 6 instances, 2 variants

**Variant 1** (3 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(3, 13, 38);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(3, 13, 38);
  font-size: 16px;
  font-weight: 400;
```

**Variant 2** (3 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(125, 137, 176);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(125, 137, 176);
  font-size: 14px;
  font-weight: 400;
```

### Card — 3 instances, 1 variant

**Variant 1** (3 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(93, 107, 152);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(93, 107, 152);
  font-size: 14px;
  font-weight: 400;
```

### Card — 1 instance, 1 variant

**Variant 1** (1 instance)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(36, 47, 71);
  padding: 36px 36px 36px 36px;
  border-radius: 0px;
  border: 0px none rgb(36, 47, 71);
  font-size: 16px;
  font-weight: 400;
```

### Other — 1 instance, 1 variant

**Variant 1** (1 instance)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(36, 47, 71);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(36, 47, 71);
  font-size: 16px;
  font-weight: 400;
```

### Button — 1 instance, 1 variant

**Variant 1** (1 instance)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(36, 47, 71);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(36, 47, 71);
  font-size: 16px;
  font-weight: 400;
```

### Button — 1 instance, 1 variant

**Variant 1** (1 instance)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(36, 47, 71);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(36, 47, 71);
  font-size: 16px;
  font-weight: 400;
```

### Button — 6 instances, 1 variant

**Variant 1** (6 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(36, 47, 71);
  padding: 0px 0px 0px 0px;
  border-radius: 0px;
  border: 0px none rgb(36, 47, 71);
  font-size: 13px;
  font-weight: 400;
```

### Button — 1 instance, 1 variant

**Variant 1** (1 instance)

```css
  background: rgb(255, 255, 255);
  color: rgb(0, 0, 0);
  padding: 9px 12px 9px 12px;
  border-radius: 12px;
  border: 1px solid rgb(220, 223, 234);
  font-size: 13.3333px;
  font-weight: 400;
```

### Link — 2 instances, 1 variant

**Variant 1** (2 instances)

```css
  background: rgba(0, 0, 0, 0);
  color: rgb(0, 0, 238);
  padding: 0px 0px 0px 0px;
  border-radius: 48px;
  border: 0px none rgb(0, 0, 238);
  font-size: 16px;
  font-weight: 400;
```

### Button — 2 instances, 1 variant

**Variant 1** (2 instances)

```css
  background: rgb(25, 114, 245);
  color: rgb(255, 255, 255);
  padding: 6px 18px 6px 18px;
  border-radius: 12px;
  border: 1px solid rgb(61, 127, 222);
  font-size: 16px;
  font-weight: 400;
```

## Layout System

**3 grid containers** and **284 flex containers** detected.

### Container Widths

| Max Width | Padding |
|-----------|---------|
| 1800px | 0px |
| 100% | 0px |
| 1040px | 0px |
| 1360px | 0px |
| 60% | 0px |
| 405px | 0px |
| 448px | 0px |
| 680px | 0px |
| 540px | 0px |

### Grid Column Patterns

| Columns | Usage Count |
|---------|-------------|
| 2-column | 2x |
| 3-column | 1x |

### Grid Templates

```css
grid-template-columns: 556px 556px;
gap: 30px 40px;
grid-template-columns: 342.656px 342.672px 342.656px;
gap: 32px;
grid-template-columns: 225px 225px;
```

### Flex Patterns

| Direction/Wrap | Count |
|----------------|-------|
| row/nowrap | 205x |
| column/nowrap | 75x |
| row/wrap | 2x |
| row-reverse/nowrap | 2x |

**Gap values:** `12px`, `14px`, `15px`, `16px`, `18px`, `24px`, `26px`, `28px`, `2px`, `30px`, `30px 40px`, `32px`, `40px`, `4px`, `5px`, `6px`, `8px`, `9px`, `normal 10px`, `normal 11px`, `normal 14px`, `normal 15px`, `normal 3px`, `normal 4px`, `normal 64px`, `normal 7px`, `normal 8px`

## Accessibility (WCAG 2.1)

**Overall Score: 100%** — 4 passing, 0 failing color pairs

### Passing Color Pairs

| Foreground | Background | Ratio | Level |
|------------|------------|-------|-------|
| `#000000` | `#ffffff` | 21:1 | AAA |
| `#5d6b98` | `#ffffff` | 5.22:1 | AA |

## Design System Score

**Overall: 74/100 (Grade: C)**

| Category | Score |
|----------|-------|
| Color Discipline | 80/100 |
| Typography Consistency | 40/100 |
| Spacing System | 100/100 |
| Shadow Consistency | 78/100 |
| Border Radius Consistency | 65/100 |
| Accessibility | 100/100 |
| CSS Tokenization | 50/100 |

**Strengths:** Well-defined spacing scale, Strong accessibility compliance

**Issues:**
- 6 font families — consider limiting to 2 (heading + body)
- 22 distinct font sizes — consider a tighter type scale
- 82 !important rules — prefer specificity over overrides
- 63% of CSS is unused — consider purging
- 3553 duplicate CSS declarations

## Gradients

**21 unique gradients** detected.

| Type | Direction | Stops | Classification |
|------|-----------|-------|----------------|
| linear | — | 2 | brand |
| linear | — | 2 | brand |
| linear | — | 2 | brand |
| linear | — | 2 | brand |
| linear | — | 2 | brand |
| linear | — | 2 | brand |
| linear | — | 2 | brand |
| linear | — | 2 | brand |
| linear | — | 2 | brand |
| linear | — | 2 | brand |
| linear | — | 2 | brand |
| linear | — | 2 | brand |
| linear | — | 2 | brand |
| linear | — | 2 | brand |
| linear | — | 2 | brand |

```css
background: linear-gradient(rgba(255, 255, 255, 0.24), rgba(255, 255, 255, 0));
background: linear-gradient(rgba(0, 0, 0, 0.02), rgba(0, 0, 0, 0.04));
background: linear-gradient(rgb(25, 114, 245), rgb(4, 86, 206));
background: linear-gradient(rgb(204, 242, 199), rgb(157, 225, 149));
background: linear-gradient(rgb(237, 226, 253), rgb(213, 192, 249));
```

## Z-Index Map

**11 unique z-index values** across 4 layers.

| Layer | Range | Elements |
|-------|-------|----------|
| modal | 1000,999999 | div.c.o.m.m.o.n.-.t.o.o.l.t.i.p._._.o.v.e.r.l.a.y, div.c.o.m.m.o.n.-.t.o.o.l.t.i.p._._.o.v.e.r.l.a.y, div.c.o.m.m.o.n.-.t.o.o.l.t.i.p._._.o.v.e.r.l.a.y |
| dropdown | 100,100 | div.p.a.g.e.-.h.e.a.d.e.r._._.c.o.n.t.a.i.n.e.r, div.p.a.g.e.-.b.o.t.t.o.m.-.b.a.r |
| sticky | 10,10 | div.c.o.m.m.o.n.-.h.e.r.o._._.w.r.a.p.p.e.r |
| base | -1,5 | div.c.o.m.m.o.n.-.d.e.c.o.r. .c.o.m.m.o.n.-.c.r.i.s.p.-.h.u.g.o.-.p.o.r.t.a.l._._.d.e.c.o.r, div.c.o.m.m.o.n.-.d.e.c.o.r. .c.o.m.m.o.n.-.c.r.i.s.p.-.h.u.g.o.-.p.o.r.t.a.l._._.d.e.c.o.r, svg.c.o.m.m.o.n.-.e.x.t.e.r.n.a.l.-.v.i.d.e.o._._.c.o.n.t.r.o.l.s.-.p.r.o.g.r.e.s.s |

**Issues:**
- [object Object]

## SVG Icons

**33 unique SVG icons** detected. Dominant style: **outlined**.

| Size Class | Count |
|------------|-------|
| xs | 4 |
| sm | 3 |
| md | 19 |
| lg | 3 |
| xl | 4 |

**Icon colors:** `currentColor`, `rgb(25, 114, 245)`, `#344055`, `#000`, `rgb(0, 0, 0)`, `rgb(90, 40, 176)`, `rgb(0, 61, 195)`, `white`, `inherit`, `#eaecf5`

## Font Files

| Family | Source | Weights | Styles |
|--------|--------|---------|--------|
| Crisp Aeonik Pro Light | self-hosted | 400, normal | normal |
| Crisp Aeonik Pro Regular | self-hosted | 400 | normal |
| Crisp Aeonik Pro Medium | self-hosted | 400 | normal |
| Crisp Aeonik Pro Bold | self-hosted | 700 | normal |
| Crisp Inter Medium | self-hosted | 400 | normal |
| Crisp Inter Bold | self-hosted | 700 | normal |

## Image Style Patterns

| Pattern | Count | Key Styles |
|---------|-------|------------|
| thumbnail | 84 | objectFit: fill, borderRadius: 0px, shape: square |
| general | 9 | objectFit: fill, borderRadius: 12px, shape: rounded |
| hero | 6 | objectFit: cover, borderRadius: 0px, shape: square |
| avatar | 3 | objectFit: contain, borderRadius: 100%, shape: circular |

**Aspect ratios:** 1:1 (53x), 3:2 (7x), 3:4 (6x), 21:9 (3x), 10.52:1 (3x), 6.36:1 (3x), 5.92:1 (3x), 16:9 (3x)

## Motion Language

**Feel:** mechanical · **Scroll-linked:** yes

### Duration Tokens

| name | value | ms |
|---|---|---|
| `xs` | `100ms` | 100 |
| `sm` | `200ms` | 200 |
| `md` | `400ms` | 400 |
| `xl` | `900ms` | 900 |

### Easing Families

- **ease-in-out** (24 uses) — `ease`
- **linear** (149 uses) — `linear`
- **ease-out** (4 uses) — `cubic-bezier(0.16, 1, 0.3, 1)`

### Keyframes In Use

| name | kind | properties | uses |
|---|---|---|---|
| `fadeInUpSmall` | slide | opacity, transform | 3 |
| `fadeInDownTiny` | slide | opacity, transform | 1 |
| `bounce` | rotate | transform | 1 |
| `slide` | slide | transform | 3 |
| `bounce` | rotate | transform | 1 |
| `slide` | slide | transform | 3 |

## Component Anatomy

### card — 118 instances

**Slots:** description, media
**Sizes:** medium

### button — 64 instances

**Slots:** label
**Sizes:** small · medium

### link — 2 instances


## Brand Voice

**Tone:** friendly · **Pronoun:** third-person · **Headings:** unknown (tight)

### Top CTA Verbs

- **learn** (14)
- **get** (11)
- **see** (5)
- **discover** (3)
- **create** (3)
- **new** (2)
- **log** (2)
- **start** (2)

### Button Copy Patterns

- "learn more" (8×)
- "get your ai agent" (6×)
- "get your ai agent
14 days free trial — all crisp features — no card required" (3×)
- "create my ai agent now" (3×)
- "see all messaging channels" (3×)
- "learn more on crisp knowledge base" (3×)
- "learn more on the crisp ai chatbot" (3×)
- "new" (2×)
- "log in" (2×)
- "start free trial" (2×)

## Page Intent

**Type:** `landing` (confidence 0.45)
**Description:** Crisp is the ultimate all-in-one AI-powered multichannel messaging platform that helps businesses connect instantly with their customers or leads who are waiting for support. With its quickly evolving

## Section Roles

Reading order (top→bottom): nav

| # | Role | Heading | Confidence |
|---|------|---------|------------|
| 0 | nav | — | 0.9 |

## Material Language

**Label:** `skeuomorphic` (confidence 0.35)

| Metric | Value |
|--------|-------|
| Avg saturation | 0.594 |
| Shadow profile | soft |
| Avg shadow blur | 0px |
| Max radius | 100px |
| backdrop-filter in use | no |
| Gradients | 21 |

## Imagery Style

**Label:** `flat-illustration` (confidence 0.16)
**Counts:** total 102, svg 80, icon 41, screenshot-like 1, photo-like 4
**Dominant aspect:** square-ish
**Radius profile on images:** soft

## Quick Start

To recreate this design in a new project:

1. **Install fonts:** Add `Crisp Aeonik Pro Regular` from Google Fonts or your font provider
2. **Import CSS variables:** Copy `variables.css` into your project
3. **Tailwind users:** Use the generated `tailwind.config.js` to extend your theme
4. **Design tokens:** Import `design-tokens.json` for tooling integration
