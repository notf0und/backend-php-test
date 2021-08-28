import React, {useEffect, useState} from 'react';
import { TransitionGroup } from 'react-transition-group';
import TodoRow from './TodoRow';
import TodoAddRow from './TodoAddRow';

const Todos = () => {
  const [todos, setTodos] = useState([]);

  const onCreateRow = (record) => {
    setTodos([...todos, record])
  }

  const onUpdateRow = (record) => {
    const newTodos = [...todos];
    const index = todos.findIndex(todo => todo.id === record.id);
    newTodos[index] = record;
    console.log({newTodos});
    setTodos(newTodos);
  }

  const onDeleteRow = (id) => {
    const filtered = todos.filter((todo) => todo.id !== id);
    setTodos(filtered);
  }

  useEffect(() => {
    fetch('todo/json')
      .then(promise => promise.json())
      .then(setTodos, error => console.log({ error }));
  }, []);

  return (
      <>
        <h1>Todo List:</h1>
        <table className="table table-striped">

          <thead>
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Description</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            { todos.map((todo) =>
              <TodoRow key={`todo-${todo.id}`}
                       todo={todo}
                       onUpdate={onUpdateRow}
                       onDelete={onDeleteRow}
              />) }
            <TodoAddRow onCreate={onCreateRow} />
          </tbody>
        </table>
      </>
  )
}

export default Todos;
