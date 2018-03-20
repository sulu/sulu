The DatePicker component builds on the top of the [react-datetime](https://github.com/YouCanBookMe/react-datetime) library. 

```javascript
initialState = {value: null};

const onChange = (newValue) => {
    setState({value: newValue});
};

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {state.value ? state.value.toLocaleDateString() : 'null'}</div>
    <DatePicker value={state.value} onChange={onChange} />
</div>
```

Default date picker with initial value set:

```javascript
initialState = {value: new Date('2017-05-30')};
const onChange = (newValue) => {
    setState({value: newValue});
};

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {state.value ? state.value.toLocaleDateString() : 'null'}</div>
    <DatePicker value={state.value} onChange={onChange} />
</div>
```

Date time picker can be created with the option property `timeFormat`.

```javascript
initialState = {value: ''};
const onChange = (newValue) => {
    setState({value: newValue});
};

const options = {
    timeFormat: true,
};

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {state.value ? state.value.toLocaleDateString() : 'null'}</div>
    <DatePicker value={state.value} onChange={onChange} options={options} />
</div>
```

Month picker can be created with the option property `dateFormat` set to `MMMM`.

```javascript
initialState = {value: ''};
const onChange = (newValue) => {
    setState({value: newValue});
};

const options = {
    dateFormat: 'MMMM',
};

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {state.value ? state.value.toLocaleDateString() : 'null'}</div>
    <DatePicker value={state.value} onChange={onChange} options={options} />
</div>
```

Year picker can be created with the option property `dateFormat` set to `YYYY`.

```javascript
initialState = {value: ''};
const onChange = (newValue) => {
    setState({value: newValue});
};

const options = {
    dateFormat: 'YYYY',
};

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {state.value ? state.value.toLocaleDateString() : 'null'}</div>
    <DatePicker value={state.value} onChange={onChange} options={options} />
</div>
```

Timepicker is also possible with options `dateFormat` to `false` and `timeFormat` to `true`.

```javascript
initialState = {value: ''};
const onChange = (newValue) => {
    setState({value: newValue});
};

const options = {
    dateFormat: false,
    timeFormat: true,
};

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {state.value ? state.value.toLocaleDateString() : 'null'}</div>
    <DatePicker value={state.value} onChange={onChange} options={options} />
</div>
```

You can tell the component that it's current value isn't valid with the prop `valid`.

Hint: Try to set the date greater than 2018-01-01 ;)

```javascript
initialState = {
    value: '',
    valid: false
};
const onChange = (newValue) => {
    setState({value: newValue, valid: newValue > new Date('2018-01-01')});
};

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {state.value ? state.value.toLocaleDateString() : 'null'}</div>
    <DatePicker value={state.value} valid={state.valid} onChange={onChange} />
</div>
```