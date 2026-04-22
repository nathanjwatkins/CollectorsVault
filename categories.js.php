const CATEGORIES = {
  cards: {
    label: 'Trading Cards', icon: '🃏',
    prompt: `You are an expert trading card identifier. Examine this image.
Return ONLY a raw JSON object. Start with { end with }. No markdown, no explanation.
{
  "name": "player or character full name",
  "subtitle": "team or franchise",
  "series": "card set name e.g. Topps Chrome",
  "cardType": "Base|Refractor|Prizm|Parallel|Auto|Relic|Patch|Rookie|Gold|Silver|1/1|Short Print",
  "year": "season e.g. 2024/25",
  "cardNumber": "if visible else empty string",
  "parallel": "variant name if applicable else empty string",
  "condition": "Mint|Near Mint|Excellent|Very Good|Good|Poor",
  "estimatedValue": 0,
  "confidence": "High|Medium|Low",
  "notes": "notable features"
}
estimatedValue must be a GBP number.`,
    fields: [
      { row: [
        { id: 'series',     label: 'Card Series',   type: 'text',   placeholder: 'e.g. Topps Chrome' },
        { id: 'cardType',   label: 'Card Type',     type: 'select', options: ['Base','Refractor','Prizm','Parallel','Auto','Relic','Patch','Rookie','Gold','Silver','1/1','Short Print','Case Hit'] },
      ]},
      { row: [
        { id: 'year',       label: 'Year / Season', type: 'text',   placeholder: '2024/25' },
        { id: 'condition',  label: 'Condition',     type: 'select', options: ['Mint','Near Mint','Excellent','Very Good','Good','Poor'] },
      ]},
      { row: [
        { id: 'cardNumber', label: 'Card #',        type: 'text',   placeholder: 'e.g. TC-100' },
        { id: 'parallel',   label: 'Parallel',      type: 'text',   placeholder: 'e.g. Blue Refractor' },
      ]},
    ],
  },

  shirts: {
    label: 'Football Shirts', icon: '👕',
    prompt: `You are an expert football shirt identifier. Examine this image.
Return ONLY a raw JSON object. Start with { end with }. No markdown.
{
  "name": "club or national team name",
  "subtitle": "player name printed on shirt if any, else empty",
  "season": "season e.g. 2023/24",
  "kitType": "Home|Away|Third|Goalkeeper|Training|Special Edition",
  "manufacturer": "e.g. Nike, Adidas, Umbro",
  "size": "shirt size if visible, else empty",
  "signed": "Yes|No|Unknown",
  "condition": "New with tags|Excellent|Good|Fair|Poor",
  "estimatedValue": 0,
  "confidence": "High|Medium|Low",
  "notes": "notable features"
}`,
    fields: [
      { row: [
        { id: 'season',       label: 'Season',       type: 'text',   placeholder: '2024/25' },
        { id: 'kitType',      label: 'Kit Type',     type: 'select', options: ['Home','Away','Third','Goalkeeper','Training','Special Edition'] },
      ]},
      { row: [
        { id: 'manufacturer', label: 'Manufacturer', type: 'text',   placeholder: 'e.g. Nike' },
        { id: 'size',         label: 'Size',         type: 'text',   placeholder: 'e.g. L, XL' },
      ]},
      { row: [
        { id: 'signed',       label: 'Signed?',      type: 'select', options: ['No','Yes','Unknown'] },
        { id: 'condition',    label: 'Condition',    type: 'select', options: ['New with tags','Excellent','Good','Fair','Poor'] },
      ]},
    ],
  },

  games: {
    label: 'Video Games', icon: '🎮',
    prompt: `You are an expert video game collector. Examine this image.
Return ONLY a raw JSON object. Start with { end with }. No markdown.
{
  "name": "exact game title",
  "subtitle": "platform e.g. PlayStation 5, Nintendo 64",
  "publisher": "publisher name",
  "genre": "e.g. Action, RPG, Sports",
  "region": "PAL|NTSC|NTSC-J|Multi-region",
  "year": "release year",
  "completeness": "Game Only|With Case|Complete in Box|Sealed",
  "condition": "Mint|Very Good|Good|Fair|Poor",
  "estimatedValue": 0,
  "confidence": "High|Medium|Low",
  "notes": "notable details"
}`,
    fields: [
      { row: [
        { id: 'subtitle',     label: 'Platform',      type: 'text',   placeholder: 'e.g. PlayStation 5' },
        { id: 'publisher',    label: 'Publisher',     type: 'text',   placeholder: 'e.g. Nintendo' },
      ]},
      { row: [
        { id: 'year',         label: 'Release Year',  type: 'text',   placeholder: 'e.g. 1998' },
        { id: 'region',       label: 'Region',        type: 'select', options: ['PAL','NTSC','NTSC-J','Multi-region'] },
      ]},
      { row: [
        { id: 'completeness', label: 'Completeness',  type: 'select', options: ['Complete in Box','With Case','Game Only','Sealed'] },
        { id: 'condition',    label: 'Condition',     type: 'select', options: ['Mint','Very Good','Good','Fair','Poor'] },
      ]},
    ],
  },

  vinyl: {
    label: 'Vinyl / Music', icon: '💿',
    prompt: `You are an expert vinyl and music memorabilia identifier. Examine this image.
Return ONLY a raw JSON object. Start with { end with }. No markdown.
{
  "name": "album or release title",
  "subtitle": "artist or band name",
  "label": "record label e.g. Parlophone",
  "format": "12-inch LP|7-inch Single|10-inch|CD|Cassette|Box Set",
  "year": "release year",
  "pressing": "e.g. Original UK 1st Press, Reissue, Picture Disc, Coloured Vinyl",
  "condition": "Mint|Near Mint|Very Good Plus|Very Good|Good|Poor",
  "estimatedValue": 0,
  "confidence": "High|Medium|Low",
  "notes": "notable features"
}`,
    fields: [
      { row: [
        { id: 'subtitle',  label: 'Artist',    type: 'text',   placeholder: 'e.g. Oasis' },
        { id: 'label',     label: 'Label',     type: 'text',   placeholder: 'e.g. Creation Records' },
      ]},
      { row: [
        { id: 'format',    label: 'Format',    type: 'select', options: ['12-inch LP','7-inch Single','10-inch','CD','Cassette','8-Track','Box Set'] },
        { id: 'year',      label: 'Year',      type: 'text',   placeholder: 'e.g. 1994' },
      ]},
      { row: [
        { id: 'pressing',  label: 'Pressing',  type: 'text',   placeholder: 'e.g. Original UK 1st Press' },
        { id: 'condition', label: 'Condition', type: 'select', options: ['Mint','Near Mint','Very Good Plus','Very Good','Good','Poor'] },
      ]},
    ],
  },

  other: {
    label: 'Other Collectibles', icon: '📦',
    prompt: `You are a collectibles expert. Examine this image.
Return ONLY a raw JSON object. Start with { end with }. No markdown.
{
  "name": "item name or title",
  "subtitle": "brand, maker, or category",
  "type": "e.g. Toy, Figurine, Poster, Book, Coin, Badge",
  "year": "year or era if identifiable",
  "material": "primary material e.g. Diecast, Resin",
  "condition": "Mint|Excellent|Good|Fair|Poor",
  "estimatedValue": 0,
  "confidence": "High|Medium|Low",
  "notes": "notable features"
}`,
    fields: [
      { row: [
        { id: 'subtitle',  label: 'Brand / Maker', type: 'text',   placeholder: 'e.g. Corgi' },
        { id: 'type',      label: 'Item Type',     type: 'text',   placeholder: 'e.g. Figurine, Poster' },
      ]},
      { row: [
        { id: 'year',      label: 'Year / Era',    type: 'text',   placeholder: 'e.g. 1980s' },
        { id: 'material',  label: 'Material',      type: 'text',   placeholder: 'e.g. Diecast' },
      ]},
      { row: [
        { id: 'condition', label: 'Condition',     type: 'select', options: ['Mint','Excellent','Good','Fair','Poor'] },
      ], full: true },
    ],
  },
};
