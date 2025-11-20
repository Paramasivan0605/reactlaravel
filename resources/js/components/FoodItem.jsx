import React from "react";

export default function FoodItem({ item, currency, onOpen }) {
  return (
    <div className="swiggy-card" onClick={onOpen}>
      <div className="swiggy-card-image">
        <img
          src={item.category_image || "/images/placeholder-food.jpg"}
          alt={item.name}
          onError={(e) => {
            e.target.style.display = 'none';
            e.target.nextSibling.style.display = 'flex';
          }}
        />
        <div style={{display: 'none', width: '100%', height: '100%', background: '#f8f8f8', alignItems: 'center', justifyContent: 'center', fontSize: '20px'}}>
          🍽️
        </div>
      </div>

      <div className="swiggy-card-info">
        <h3 className="swiggy-food-name">{item.name}</h3>

        {item.description && (
          <p className="swiggy-food-desc">{item.description}</p>
        )}

        <div className="swiggy-card-bottom">
          <span className="swiggy-price">
            {currency} {parseFloat(item.price).toFixed(2)}
          </span>
          <button className="swiggy-add-btn">ADD +</button>
        </div>
      </div>
    </div>
  );
}