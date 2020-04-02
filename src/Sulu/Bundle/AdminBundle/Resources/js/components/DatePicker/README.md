The DatePicker component builds on the top of the [react-datetime](https://github.com/YouCanBookMe/react-datetime) library. 

```javascript
const [value, setValue] = React.useState(null);

const onChange = (value) => setValue(value);

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {value ? value.toLocaleDateString() : 'null'}</div>
    <DatePicker value={value} onChange={onChange} />
</div>
```

Default date picker with initial value set:

```javascript
const [value, setValue] = React.useState(null);

const onChange = (value) => setValue(value);

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {value ? value.toLocaleDateString() : 'null'}</div>
    <DatePicker value={value} onChange={onChange} />

    <button onClick={() => onChange(new Date('2017-03-10'))}>Test</button>
</div>
```

Date time picker can be created with the option property `timeFormat`.

```javascript
const [value, setValue] = React.useState(null);

const onChange = (value) => setValue(value);

const options = {
    timeFormat: true,
};

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {value ? value.toLocaleDateString() : 'null'}</div>
    <DatePicker value={value} onChange={onChange} options={options} />
</div>
```

Month picker can be created with the option property `dateFormat` set to `MMMM`.

```javascript
const [value, setValue] = React.useState(null);

const onChange = (value) => setValue(value);

const options = {
    dateFormat: 'MMMM',
};

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {value ? value.toLocaleDateString() : 'null'}</div>
    <DatePicker value={value} onChange={onChange} options={options} />
</div>
```

Year picker can be created with the option property `dateFormat` set to `YYYY`.

```javascript
const [value, setValue] = React.useState(null);

const onChange = (value) => setValue(value);

const options = {
    dateFormat: 'YYYY',
};

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {value ? value.toLocaleDateString() : 'null'}</div>
    <DatePicker value={value} onChange={onChange} options={options} />
</div>
```

Timepicker is also possible with options `dateFormat` to `false` and `timeFormat` to `true`.

```javascript
const [value, setValue] = React.useState(null);

const onChange = (value) => setValue(value);

const options = {
    dateFormat: false,
    timeFormat: true,
};

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {value ? value.toLocaleDateString() : 'null'}</div>
    <DatePicker value={value} onChange={onChange} options={options} />
</div>
```

You can tell the component that it's current value isn't valid with the prop `valid`.

Hint: Try to set the date greater than 2018-01-01 ;)

```javascript
const [value, setValue] = React.useState('');
const [valid, setValid] = React.useState(false);

const onChange = (value) => {
    setValue(value);
    setValid(value > new Date('2018-01-01'));
};

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {value ? value.toLocaleDateString() : 'null'}</div>
    <DatePicker value={value} valid={valid} onChange={onChange} />
</div>
```
