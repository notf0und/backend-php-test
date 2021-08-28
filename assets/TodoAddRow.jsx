import React, {createRef, useRef, useState} from 'react';

const TodoAddRow = ({ onCreate }) => {
  const [isValid, setIsValid] = useState(true);
  const input = useRef();

  const add = (record) => {
    input.current.value = '';
    onCreate(record)
  }

  const onClickAdd = () => {
    const description = input.current.value;
    setIsValid(true);

    if(!description) {
      setIsValid(false);
      return;
    }

    fetch('/todo/add/json', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ description })
    })
      .then(response => response.json())
      .then(add, error => console.log({ error }));
  }

  return (
    <tr>
        <td colSpan="3">
          <input ref={input} type="text" name="description" className="small-6 small-center" placeholder="Description..." />
          { !isValid && <span className="help-block">Field is required</span> }
        </td>
        <td className="add-todo">
          <button onClick={onClickAdd} className="btn btn-sm btn-primary">Add</button>
        </td>
    </tr>
  )
}

export default TodoAddRow;
