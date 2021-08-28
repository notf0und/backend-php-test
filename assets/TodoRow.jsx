import React, { useState } from 'react';

const TodoRow = ({ todo, onUpdate, onDelete }) => {
  const [deleting, setDeleting] = useState(false);
  const { id, user_id, description, completed } = todo;

  const completeTodo = () => {
    onUpdate({...todo, completed: '1' });
  }

  const deleteTodo = () => {
    setDeleting(true);
    setTimeout(() => {
      onDelete(id);
      setDeleting(false);
    }, 300);
  }

  const post = (url, callback) => {
    fetch(url, { method: 'POST'})
      .then(response => response.json())
      .then(callback, error => console.log({ error }));
  }

  const onClickComplete = () => post(`/todo/complete/${id}/json`, completeTodo);
  const onClickDelete = () => post(`/todo/delete/${id}/json`, deleteTodo);

  return (
    <tr className={deleting && 'fade'}>
      <td>{id}</td>
      <td>{user_id}</td>
      <td>
        <a href={`todo/${id}`}>
          {completed === '1' && <strike>{description}</strike>}
          {completed === '0' && description}
        </a>
      </td>
      <td className="actions">
        <div>
          <button
            className={`btn btn-xs ${ completed === '0' ? 'btn-info' : 'btn-success'}`}
            onClick={onClickComplete}
            disabled={completed !== '0'}
          >
            <span className="glyphicon glyphicon-ok glyphicon-white" />
          </button>
        </div>
        <div>
          <button
            className="btn btn-xs btn-danger"
            onClick={onClickDelete}>
          <span
            className="glyphicon glyphicon-remove glyphicon-white" />
          </button>
        </div>
      </td>
    </tr>
  )
}

export default TodoRow;
