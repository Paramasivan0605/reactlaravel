import React, { useState } from "react";
import FoodItem from "./FoodItem";
import FoodItemModal from "./FoodItemModal";

export default function FoodGrid({ foods = [], activeCategory, currency }) {
  const [selected, setSelected] = useState(null);

  const visible = activeCategory === "all" 
    ? foods 
    : foods.filter(f => String(f.category_id) === String(activeCategory));

  // Group by category for better organization
  const groupedFoods = visible.reduce((acc, item) => {
    const category = item.category || 'Uncategorized';
    if (!acc[category]) {
      acc[category] = [];
    }
    acc[category].push(item);
    return acc;
  }, {});

  return (
    <section className="swiggy-grid-section">
      <h2 className="section-title">
        {activeCategory === 'all' ? 'All Items' : 'Menu Items'} 
        {visible.length > 0 && ` (${visible.length})`}
      </h2>

      {visible.length === 0 ? (
        <div className="empty-state">
          <div>🍽️</div>
          <p>No items found in this category</p>
        </div>
      ) : (
        <div className="swiggy-food-grid">
          {Object.entries(groupedFoods).map(([category, items]) => (
            <React.Fragment key={category}>
              {activeCategory === 'all' && (
                <div className="category-section-header">
                  <h3 className="category-title">{category}</h3>
                  <div className="category-divider"></div>
                </div>
              )}
              {items.map(item => (
                <FoodItem
                  key={item.id}
                  item={item}
                  currency={currency}
                  onOpen={() => setSelected(item)}
                />
              ))}
            </React.Fragment>
          ))}
        </div>
      )}

      <FoodItemModal
        item={selected}
        onClose={() => setSelected(null)}
        currency={currency}
      />
    </section>
  );
}