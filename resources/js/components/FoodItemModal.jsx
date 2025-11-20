import React from 'react';

export default function FoodItemModal({ item, onClose, currency }) {
  if (!item) return null;

  return (
    <div className="lm-modal-backdrop" onMouseDown={onClose}>
      <div className="lm-modal lm-item-modal" onMouseDown={e=>e.stopPropagation()}>
        <div className="modal-bg">
          {item.category_image ? <img src={item.category_image} alt=""/> : <div className="gradient-bg"/>}
        </div>
        <div className="modal-header">
          <h3>{item.name}</h3>
          <button className="btn-close" onClick={onClose}>×</button>
        </div>
        <div className="modal-body">
          {item.category && <div className="modal-category">{item.category}</div>}
          {item.description && <div className="description">{item.description}</div>}

          <div className="price-box">
            <div>
              <div className="price-label">Price</div>
              <div className="price-value">{currency} {parseFloat(item.price).toFixed(2)}</div>
            </div>
            <button className="confirm-btn" onClick={()=>{ /* call add to cart API here */ onClose(); }}>Add to Cart</button>
          </div>
        </div>
      </div>
    </div>
  );
}
