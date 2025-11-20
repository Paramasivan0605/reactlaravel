import React from 'react';

export default function CategoryList({ categories = [], activeCategory, setActiveCategory }) {
  return (
    <div className="lm-categories container">
      <div className="lm-categories-scroll">
        <button className={`lm-category ${activeCategory==='all' ? 'active':''}`} onClick={()=>setActiveCategory('all')}>
          <div className="cat-emoji">🍽️</div>
          <h4>All Items</h4>
        </button>

        {categories.map(cat => (
          <button key={cat.id} className={`lm-category ${activeCategory==cat.id ? 'active':''}`} onClick={()=>setActiveCategory(cat.id)}>
            <div className="category-icon">
              {cat.image ? <img src={cat.image} alt={cat.name} /> : '🍴'}
            </div>
            <h4>{cat.name}</h4>
          </button>
        ))}
      </div>
    </div>
  );
}
