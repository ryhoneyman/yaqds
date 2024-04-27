function event_say(e)
	if(e.message:findi("hail")) then
		e.other:Message(0, "The weapon polisher glances at you while polishing an Efreeti horn, 'Ho, hum. A slave to an Efreeti is never done. Master Dojorn has entrusted me to polish specific [equipment] he's acquired.'");
	elseif(e.message:findi("equipment")) then
		e.other:Message(0,"If you are a disciple of Sky and need your Efreeti War Horn, Standard, Ring, or Spear polished, I am the tradesmith for you. Please hand these and I will polish, sharpen, or fit them free of charge.");
	end
end


function event_trade(e)
	local item_lib = require("items");
	if(item_lib.check_turn_in(e.self, e.trade, {item1 = 20831})) then
		e.self:Say("Excellent. Please accept this Sharpened Efreeti Spear in return.");
		e.other:SummonCursorItem(30774);
	elseif(item_lib.check_turn_in(e.self, e.trade, {item1 = 20763})) then
		e.self:Say("Excellent. Please accept this Golden Efreeti Band in return.");
		e.other:SummonCursorItem(30771);
	elseif(item_lib.check_turn_in(e.self, e.trade, {item1 = 20830})) then
		e.self:Say("Excellent. Please accept this Polished Efreeti Horn in return.");
		e.other:SummonCursorItem(30773);
	elseif(item_lib.check_turn_in(e.self, e.trade, {item1 = 20817})) then
		e.self:Say("Excellent. Please accept this Sharpened Efreeti Standard in return.");
		e.other:SummonCursorItem(30772);
	end
end